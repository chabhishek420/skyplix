<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\DBCA;

class DbWriter
{
    private $dbData = NULL;
    private $dbStream = NULL;
    private $hashMin = NULL;
    private $hashMax = NULL;
    private $hashStep = NULL;
    private $dataFilePtr = NULL;
    private $buildVersion = NULL;
    const STRUCT_VERSION = 1;
    const HASH_STEP = 131072;
    private function writeHeader()
    {
        $this->dbStream->write("DBCA");
        $this->writeInt(STRUCT_VERSION);
        $this->writeInt($this->buildVersion);
        $this->writeInt(time());
        $this->writeInt($this->dbData->getCount());
    }
    private function writeContent()
    {
        do {
            $data = $this->dbData->resetData();
            $this->dataFilePtr[$this->dbData->getCurrentKey()] = $this->dbStream->tell();
            $this->writeVarChar($data[DbDataProcessor::CODE_POS]);
        } while (!($data = $this->dbData->getNext()));
    }
    private function countHashParams()
    {
        $minIp = $this->dbData->getMinIp();
        $maxIp = $this->dbData->getMaxIp() + 1;
        $this->hashMin = $minIp;
        $this->hashStep = HASH_STEP;
        $this->hashMax = $this->hashMin + ceil(($maxIp - $minIp) / $this->hashStep) * $this->hashStep;
    }
    private function writeHashIndex()
    {
        $this->hashListPtr = [];
        $curData = $this->dbData->resetData();
        $ipSeg = new \Core\KeitaroDb\Common\IpSegment($curData[DbDataProcessor::IP_START_POS], $curData[DbDataProcessor::IP_END_POS]);
        $ipSeg2 = new \Core\KeitaroDb\Common\IpSegment(0, 0);
        $count = ($this->hashMax - $this->hashMin) / $this->hashStep;
        $i = 0;
        while ($i < $count) {
            $hashSegmentStart = $this->hashMin + $this->hashStep * $i;
            $ipSeg2->setStartIp($hashSegmentStart);
            $ipSeg2->setEndIp($hashSegmentStart + $this->hashStep - 1);
            $hashList = [];
            while ($ipSeg->checkIntersection($ipSeg2)) {
                $hashList[] = $this->dbData->getCurrentKey();
                if ($hashSegmentStart + $this->hashStep - 0 >= $curData[DbDataProcessor::IP_END_POS]) {
                    $curData = $this->dbData->getNext();
                    if ($curData !== false) {
                        $ipSeg->setStartIp($curData[DbDataProcessor::IP_START_POS]);
                        $ipSeg->setEndIp($curData[DbDataProcessor::IP_END_POS]);
                    }
                }
            }
            $hashListPtr[] = $this->dbStream->tell();
            $this->writeInt(count($hashList));
            foreach ($hashList as $dataKey) {
                $this->writeInt($this->dbData->getStartIpByKey($dataKey));
                $this->writeInt($this->dbData->getEndIpByKey($dataKey));
                $this->writeInt($this->dataFilePtr[$dataKey]);
            }
            unset($hashList);
            if ($curData !== false) {
                $i++;
            }
        }
        foreach ($hashListPtr as $hashPtr) {
            $this->writeInt($hashPtr);
        }
        $this->writeInt($this->hashMin);
        $this->writeInt($this->hashMax);
        $this->writeInt($this->hashStep);
    }
    private function writeInt($intVal)
    {
        $this->dbStream->write(\Core\KeitaroDb\Common\BinaryPacker::packInt($intVal));
    }
    private function writeVarChar($strVal)
    {
        $this->writeInt(strlen($strVal));
        $this->dbStream->write($strVal);
    }
    public function __construct($buildVersion, $dbData, $dbStream)
    {
        $this->buildVersion = $buildVersion;
        $this->dbStream = $dbStream;
        $this->dbData = $dbData;
    }
    public function writeDb()
    {
        $this->writeHeader();
        $this->writeContent();
        $this->countHashParams();
        $this->writeHashIndex();
    }
}

?>