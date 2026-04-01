<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\Common;

class IpSegment
{
    private $startIp = NULL;
    private $endIp = NULL;
    public $data = false;
    private function setIp($inVal, $mVal)
    {
        if (is_numeric($inVal)) {
            $mVal = (int) $inVal;
        } else {
            $mVal = (int) sprintf("%u", ip2long($inVal));
        }
    }
    private function getIp($mVal, $asString = true)
    {
        if ($asString) {
            return long2ip($mVal);
        }
        return $mVal;
    }
    public function __construct($startIp, $endIp, $data = false)
    {
        $this->setStartIp($startIp);
        $this->setEndIp($endIp);
        $this->data = $data;
    }
    public function setCidr($cidrIp, $data = false)
    {
        $cidr = explode("/", $cidrIp);
        if (count($cidr) != 2) {
            throw new \Exception("Не корректный cidr " . $cidrIp);
        }
        $this->setStartIp(long2ip(ip2long($cidr[0]) & -1 << 32 - (int) $cidr[1]));
        $this->setEndIp(long2ip(ip2long($cidr[0]) + pow(2, 32 - (int) $cidr[1]) - 1));
        $this->data = $data;
    }
    public function setStartIp($startIp)
    {
        $this->setIp($startIp, $this->startIp);
    }
    public function setEndIp($endIp)
    {
        $this->setIp($endIp, $this->endIp);
    }
    public function getStartIpString()
    {
        return $this->getIp($this->startIp);
    }
    public function getEndIpString()
    {
        return $this->getIp($this->endIp);
    }
    public function getStartIpFloat()
    {
        return $this->getIp($this->startIp, false);
    }
    public function getEndIpFloat()
    {
        return $this->getIp($this->endIp, false);
    }
    public function checkIntersection($otherIpSeg)
    {
        return $otherIpSeg->getStartIpFloat() <= $this->getEndIpFloat() && $this->getStartIpFloat() <= $otherIpSeg->getEndIpFloat();
    }
    public function getIntersection($otherIpSeg)
    {
        $min = max($this->getStartIpFloat(), $otherIpSeg->getStartIpFloat());
        $max = min($this->getEndIpFloat(), $otherIpSeg->getEndIpFloat());
        if ($max < $min) {
            throw new \Exception("Не пересекающиеся интервалы");
        }
        return new IpSegment($min, $max);
    }
    public function cropSegments($segmentArray)
    {
        $result = [];
        $sourceMin = $this->getStartIpFloat();
        $sourceMax = $this->getEndIpFloat();
        $lastSegment = new IpSegment($sourceMin, $sourceMax, $this->data);
        foreach ($segmentArray as $cropSegment) {
            if ($sourceMin < $cropSegment->getStartIpFloat() - 0 && $cropSegment->getStartIpFloat() <= $sourceMax) {
                $result[] = new IpSegment($sourceMin, $cropSegment->getStartIpFloat() - 1, $this->data);
            }
            $sourceMin = $cropSegment->getEndIpFloat() + 1;
            if ($cropSegment->getEndIpFloat() + 0 < $sourceMin) {
                if ($lastSegment) {
                    $lastSegment->setStartIp($cropSegment->getEndIpFloat() + 1);
                }
            } else {
                $lastSegment = NULL;
            }
        }
        if (!empty($lastSegment) && $lastSegment->getStartIpFloat() <= $lastSegment->getEndIpFloat()) {
            $result[] = $lastSegment;
        }
        return $result;
    }
    public function isInsideIp($ip)
    {
        if ($this->getStartIpFloat() <= $ip && $ip <= $this->getEndIpFloat()) {
            return true;
        }
        return false;
    }
}

?>