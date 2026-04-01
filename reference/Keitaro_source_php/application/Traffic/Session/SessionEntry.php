<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session;

class SessionEntry
{
    private $_data = [];
    const STREAMS = "streams";
    const CAMPAIGNS = "campaigns";
    const EXPIRE_DAYS = 2;
    public function __construct($data)
    {
        if (is_array($data)) {
            $this->_data = $data;
        }
    }
    public function getId()
    {
        return isset($this->_data["id"]) ? $this->_data["id"] : NULL;
    }
    public function getData()
    {
        return $this->_data;
    }
    public static function restore($data)
    {
        $obj = new SessionEntry($data);
        return $obj;
    }
    public function cleanOldSessionForCookies()
    {
        $expire = time() - EXPIRE_DAYS * 24 * 3600;
        if (isset($this->_data[STREAMS])) {
            $this->_data[STREAMS] = self::checkArray($this->_data[STREAMS], $expire);
        }
        if (isset($this->_data[CAMPAIGNS])) {
            $this->_data[CAMPAIGNS] = self::checkArray($this->_data[CAMPAIGNS], $expire);
        }
        return $this;
    }
    public static function checkArray($arr, $timestamp)
    {
        if (is_array($arr) && 0 < count($arr)) {
            foreach ($arr as $id => $time) {
                if ($time < $timestamp) {
                    unset($arr[$id]);
                }
            }
        }
        return $arr;
    }
    public function touch(\Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign, \Traffic\Model\BaseStream $stream = NULL)
    {
        $datetime = $rawClick->getDateTime();
        if (!empty($stream)) {
            $currentTime = empty($this->_data[STREAMS][$stream->getId()]) ? 0 : $this->_data[STREAMS][$stream->getId()];
            if (!$currentTime || $campaign->getCookiesTtl() * 60 * 60 <= $datetime->getTimestamp() - $currentTime) {
                $this->_data[STREAMS][$stream->getId()] = $datetime->getTimestamp();
            }
        }
        $currentTime = empty($this->_data[CAMPAIGNS][$campaign->getId()]) ? 0 : $this->_data[CAMPAIGNS][$campaign->getId()];
        if (!$currentTime || $campaign->getCookiesTtl() * 60 * 60 <= $datetime->getTimestamp() - $currentTime) {
            $this->_data[CAMPAIGNS][$campaign->getId()] = $datetime->getTimestamp();
        }
        $currentTime = empty($this->_data["time"]) ? 0 : $this->_data["time"];
        if (!$currentTime || $campaign->getCookiesTtl() * 60 * 60 <= $datetime->getTimestamp() - $currentTime) {
            $this->_data["time"] = $datetime->getTimestamp();
        }
        return $this;
    }
    public function isUniqueForCampaign(\Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign)
    {
        if (!isset($this->_data[CAMPAIGNS][$campaign->getId()])) {
            return true;
        }
        $value = $this->_data[CAMPAIGNS][$campaign->getId()];
        return !$this->_isActive($value, $rawClick->getDateTime(), $campaign->getCookiesTtl());
    }
    public function isUniqueForStream(\Traffic\RawClick $visitor, \Traffic\Model\Campaign $campaign, \Traffic\Model\BaseStream $stream)
    {
        if (!isset($this->_data[STREAMS][$stream->getId()])) {
            return true;
        }
        $value = $this->_data[STREAMS][$stream->getId()];
        return !$this->_isActive($value, $visitor->getDateTime(), $campaign->getCookiesTtl());
    }
    public function isUniqueGlobal(\Traffic\RawClick $rawClick, \Traffic\Model\Campaign $campaign)
    {
        if (!isset($this->_data[CAMPAIGNS])) {
            return true;
        }
        if (isset($this->_data["time"])) {
            $value = $this->_data["time"];
            if (!$rawClick->getDateTime() instanceof \DateTime) {
                throw new \Exception("RawClick#date_time must be DateTime");
            }
            return !$this->_isActive($value, $rawClick->getDateTime(), $campaign->getCookiesTtl());
        }
        return true;
    }
    private function _isActive($value, \DateTime $datetime, $expireHours)
    {
        return $datetime->getTimestamp() < $value + $expireHours * 60 * 60;
    }
}

?>