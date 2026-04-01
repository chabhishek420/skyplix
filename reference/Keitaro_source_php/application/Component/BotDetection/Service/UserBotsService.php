<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Service;

class UserBotsService extends \Traffic\Service\AbstractService implements \Component\BotDetection\UserBotsServiceInterface
{
    public function cmpArray($a, $b)
    {
        $a = $a[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS];
        $b = $b[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS];
        if ($a == $b) {
            return 0;
        }
        if ($b < $a) {
            return 1;
        }
        if ($a < $b) {
            return -1;
        }
    }
    public static function mapItem($item)
    {
        $item = trim($item);
        if (empty($item)) {
            return false;
        }
        if (@strstr($item, "/")) {
            if (!\Traffic\Tools\Tools::isValidCIDR($item)) {
                return false;
            }
            $range = \Traffic\Tools\Tools::CIDRToRange($item);
        } else {
            if (@strstr($item, "-")) {
                $range = explode("-", $item);
                if (count($range) != 2) {
                    return false;
                }
                for ($i = 0; $i < 2; $i++) {
                    $range[$i] = trim($range[$i]);
                    if (!filter_var($range[$i], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                        return false;
                    }
                }
            } else {
                if (filter_var($item, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $range = [$item, $item];
                } else {
                    return false;
                }
            }
        }
        $result = [(int) sprintf("%u", ip2long($range[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS])), (int) sprintf("%u", ip2long($range[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS])), $item];
        return $result;
    }
    private function _parseInput($content)
    {
        $content = str_replace(",", "\n", $content);
        $this->_validateSingle($content);
        $items = explode("\n", $content);
        $items = array_map(function ($item) {
            return UserBotsService::mapItem($item);
        }, $items);
        return $this->_prepareResultList($items);
    }
    private function _validateSingle($content)
    {
        if (!preg_match("/\n/", trim($content)) && !UserBotsService::mapItem($content)) {
            $message = \Core\Locale\LocaleService::t("bot.error_add") . " " . $content;
            throw new \Core\Application\Exception\Error($message);
        }
    }
    private function _checkIntersection($x, $y)
    {
        return $x[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] <= $y[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS] && $y[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] <= $x[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS];
    }
    private function _getUnion($x, $y)
    {
        $min = min($x[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS], $y[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS]);
        $max = max($x[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS], $y[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS]);
        if ($max < $min) {
            throw new \Exception("Non intersecting intervals");
        }
        return [$min, $max, ""];
    }
    private function _fillRawValue($in)
    {
        $min = $in[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS];
        $max = $in[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS];
        $raw = "";
        if ($min == $max) {
            $raw = long2ip($min);
        } else {
            $raw = long2ip($min) . "-" . long2ip($max);
        }
        $in[\Component\BotDetection\Model\UserBotIp::RAW_VAL_POS] = $raw;
        return $in;
    }
    private function _mergeIntersectedInSortedList($list)
    {
        $finalList = [];
        $current = $list[0];
        foreach ($list as $interval) {
            if ($this->_checkIntersection($current, $interval)) {
                $current = $this->_getUnion($current, $interval);
            } else {
                $finalList[] = $this->_fillRawValue($current);
                $current = $interval;
            }
        }
        if (end($finalList) != $current) {
            $finalList[] = $this->_fillRawValue($current);
        }
        return $finalList;
    }
    private function _prepareResultList($list)
    {
        $list = array_unique($list, SORT_REGULAR);
        $list = array_filter($list);
        usort($list, [$this, "cmpArray"]);
        return $this->_mergeIntersectedInSortedList($list);
    }
    private function _storeListInStorages($list)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("Saving bots after checking: " . count($list));
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getMainStorage()->saveList($list);
    }
    private function _cropRanges($src, $excludeList)
    {
        $result = [];
        $sourceMin = $src[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS];
        $sourceMax = $src[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS];
        foreach ($excludeList as $excludeRange) {
            if ($excludeRange[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] <= $sourceMin && $sourceMin <= $excludeRange[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS]) {
                $sourceMin = $excludeRange[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS] + 1;
            } else {
                if ($sourceMin < $excludeRange[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] && $excludeRange[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] <= $sourceMax) {
                    $result[] = [$sourceMin, $excludeRange[\Component\BotDetection\Model\UserBotIp::MIN_IP_POS] - 1, ""];
                    $sourceMin = $excludeRange[\Component\BotDetection\Model\UserBotIp::MAX_IP_POS] + 1;
                }
            }
            if ($sourceMax < $sourceMin) {
                if ($sourceMin <= $sourceMax) {
                    $result[] = [$sourceMin, $sourceMax, ""];
                }
                return $result;
            }
        }
    }
    public function saveList($content)
    {
        $content = trim($content);
        if (empty($content)) {
            return $this->cleanList();
        }
        $items = $this->_parseInput($content);
        $items = $this->_prepareResultList($items);
        $this->_storeListInStorages($items);
    }
    public function addToList($content)
    {
        $newItems = $this->_parseInput($content);
        \Traffic\Logging\Service\LoggerService::instance()->debug("Adding bots: " . count($newItems));
        $oldItems = \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getMainStorage()->getList();
        $newItems = array_merge($newItems, $oldItems);
        $newItems = $this->_prepareResultList($newItems);
        $this->_storeListInStorages($newItems);
    }
    public function excludeFromList($content)
    {
        $excludeItems = $this->_parseInput($content);
        $oldItems = \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getMainStorage()->getList();
        $result = [];
        foreach ($oldItems as $oldItem) {
            $newIntervals = false;
            $newItems = $this->_cropRanges($oldItem, $excludeItems);
            foreach ($newItems as $newItem) {
                $result[] = $this->_fillRawValue($newItem);
            }
        }
        $this->_storeListInStorages($result);
    }
    public function cleanList()
    {
        \Component\BotDetection\Repository\UserBotsStorageRepository::instance()->getMainStorage()->clear();
    }
}

?>