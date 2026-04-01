<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class DbcaClient
{
    private $structVersion = NULL;
    private $buildVersion = NULL;
    private $buildTimestamp = NULL;
    private $count = NULL;
    private $hashMin = NULL;
    private $hashMax = NULL;
    private $hashStep = NULL;
    private $hashListCount = NULL;
    private $fp = NULL;
    private $_definition = NULL;
    private $_path = NULL;
    private $_fileName = NULL;
    const STRUCT_VERSION = 1;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function getDbInfo()
    {
        $this->_load();
        $result = [];
        $result["structVersion"] = $this->structVersion;
        $result["buildVersion"] = $this->buildVersion;
        $result["buildTimestamp"] = $this->buildTimestamp;
        $result["recCount"] = $this->count;
        return $result;
    }
    public function rawInfo($ip)
    {
        $this->_load();
        $ipFloat = (int) sprintf("%u", ip2long($ip));
        if (!$ipFloat || $ipFloat < $this->hashMin || $this->hashMax <= $ipFloat) {
            return NULL;
        }
        $pos = $this->hashFunc($ipFloat);
        fseek($this->fp, -12 - $this->hashListCount * 4 + $pos * 4, SEEK_END);
        $ptr = $this->unpackInt(fread($this->fp, 4));
        fseek($this->fp, $ptr);
        $listCount = $this->unpackInt(fread($this->fp, 4));
        $found = false;
        if ($listCount == 0) {
            return NULL;
        }
        $codePtrs = NULL;
        $buf = fread($this->fp, $listCount * 12);
        $i = 0;
        while ($i < $listCount) {
            $curSegmentMin = $this->unpackInt(substr($buf, $i * 12, 4));
            if ($ipFloat < $curSegmentMin) {
                return NULL;
            }
            $curSegmentMax = $this->unpackInt(substr($buf, 4 + $i * 12, 4));
            if ($curSegmentMin <= $ipFloat && $ipFloat <= $curSegmentMax) {
                $codePtrs = $this->unpackInt(substr($buf, 8 + $i * 12, 4));
                $found = true;
            } else {
                $i++;
            }
        }
        if (!$found) {
            return NULL;
        }
        fseek($this->fp, $codePtrs);
        $varCharSize = $this->unpackInt(fread($this->fp, 4));
        return fread($this->fp, $varCharSize);
    }
    private function _load()
    {
        $fileName = $this->_definition->filePath();
        if ($this->_fileName == $fileName && !empty($fileName)) {
            return NULL;
        }
        if (!empty($this->fp)) {
            fclose($this->fp);
        }
        \Traffic\Logging\Service\LoggerService::instance()->debug("[DBCA] using " . $fileName);
        if (!file_exists($fileName)) {
            throw new \Component\GeoDb\Error\DbNotFound("Can't find file " . $fileName . " for db " . $this->_definition->id());
        }
        $this->fp = fopen($fileName, "rb");
        if (!$this->fp) {
            throw new \Component\GeoDb\Error\DbError("Can't open file " . $fileName . " for db " . $this->_definition->id());
        }
        fseek($this->fp, 4);
        $buf = fread($this->fp, 16);
        $this->structVersion = $this->unpackInt(substr($buf, 0, 4));
        if (STRUCT_VERSION != $this->structVersion) {
            throw new \Component\GeoDb\Error\DbError("Incorrect db structure (v" . $this->structVersion . ")");
        }
        $this->buildVersion = $this->unpackInt(substr($buf, 4, 4));
        $this->buildTimestamp = $this->unpackInt(substr($buf, 8, 4));
        $this->count = $this->unpackInt(substr($buf, 12, 4));
        fseek($this->fp, -12, SEEK_END);
        $buf = fread($this->fp, 12);
        $this->hashMin = $this->unpackInt(substr($buf, 0, 4));
        $this->hashMax = $this->unpackInt(substr($buf, 4, 4));
        $this->hashStep = $this->unpackInt(substr($buf, 8, 4));
        $this->hashListCount = ($this->hashMax - $this->hashMin) / $this->hashStep;
        $this->_fileName = $fileName;
    }
    private function _getPath()
    {
        return $this->_path;
    }
    private function hashFunc($val)
    {
        return floor(($val - $this->hashMin) / $this->hashStep);
    }
    private function packInt($val)
    {
        $buf = pack("L", (int) $val);
        return $buf;
    }
    private function unpackInt($buf)
    {
        $unpackedVal = unpack("L", $buf);
        $unpackedVal = $unpackedVal[1];
        if ($unpackedVal < 0) {
            $unpackedVal += 0;
        }
        return (int) $unpackedVal;
    }
}

?>