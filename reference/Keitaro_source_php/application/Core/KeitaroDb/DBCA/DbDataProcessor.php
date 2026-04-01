<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\DBCA;

class DbDataProcessor
{
    private $curIpSeg = NULL;
    private $curCode = NULL;
    private $data = [];
    const IP_START_POS = 0;
    const IP_END_POS = 1;
    const CODE_POS = 2;
    private function addRecord()
    {
        $row = [IP_START_POS => $this->curIpSeg->getStartIpFloat(), IP_END_POS => $this->curIpSeg->getEndIpFloat(), CODE_POS => $this->curCode];
        $this->data[] = $row;
    }
    private function _getEndIpByPos($key)
    {
        return $this->data[$key][IP_END_POS];
    }
    private function removeDuplicates()
    {
        $lastIp = NULL;
        $lastKey = NULL;
        foreach ($this->data as $key => $val) {
            if ($lastIp && $this->data[$key][IP_START_POS] <= $lastIp) {
                if ($this->_getEndIpByPos($lastKey) <= $this->_getEndIpByPos($key)) {
                    $this->data[$lastKey][CODE_POS] = $this->data[$key][CODE_POS];
                    $this->data[$lastKey][IP_END_POS] = $this->_getEndIpByPos($key);
                    $lastIp = $this->data[$lastKey][IP_END_POS];
                }
                unset($this->data[$key]);
            } else {
                $lastIp = $this->data[$key][IP_END_POS];
                $lastKey = $key;
            }
        }
    }
    public function getMinIp()
    {
        $first = reset($this->data);
        return $first[IP_START_POS];
    }
    public function getMaxIp()
    {
        $last = end($this->data);
        return $last[IP_END_POS];
    }
    public function resetData()
    {
        return reset($this->data);
    }
    public function getNext()
    {
        return next($this->data);
    }
    public function getCurrentKey()
    {
        return key($this->data);
    }
    public function getStartIpByKey($key)
    {
        return $this->data[$key][IP_START_POS];
    }
    public function getEndIpByKey($key)
    {
        return $this->data[$key][IP_END_POS];
    }
    public function getCount()
    {
        return count($this->data);
    }
    public function addInterval(\Core\KeitaroDb\Common\IpSegment $ipSeg, $code)
    {
        if ($code === $this->curCode && $ipSeg->getStartIpFloat() - $this->curIpSeg->getEndIpFloat() < 0 && 0 < $ipSeg->getStartIpFloat() - $this->curIpSeg->getEndIpFloat()) {
            $pos = count($this->data) - 1;
            $this->curIpSeg->setEndIp($ipSeg->getEndIpFloat());
            $this->data[$pos][IP_END_POS] = $ipSeg->getEndIpFloat();
        } else {
            $this->curIpSeg = $ipSeg;
            $this->curCode = $code;
            $this->addRecord();
        }
    }
    public function sort()
    {
        usort($this->data, function ($a, $b) {
            if ($a[0] == $b[0]) {
                return 0;
            }
            return $a[0] < $b[0] ? -1 : 1;
        });
        $this->removeDuplicates();
    }
}

?>