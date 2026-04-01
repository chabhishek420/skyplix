<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetStreams implements DataGetterInterface
{
    const NAME = "streams";
    const CACHE_KEY = "STRMS";
    public function name()
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (empty($scope["campaign_id"])) {
            throw new \Exception("No 'campaign_id' in scope");
        }
        $key = self::cacheKey($scope["campaign_id"]);
        $groupedRows = $storage->get($key);
        return $this->_groupedRowsToEntities($groupedRows);
    }
    public function fallback($scope = NULL)
    {
        $campaignId = $scope["campaign_id"];
        $groupedRows = self::getGroupedStreams([$campaignId]);
        if (empty($groupedRows)) {
            return $groupedRows;
        }
        return $this->_groupedRowsToEntities($groupedRows[$campaignId]);
    }
    public function _groupedRowsToEntities($groupedRows)
    {
        $result = [];
        foreach ($groupedRows as $type => $rows) {
            $subResult = [];
            foreach ($rows as $key => $row) {
                $subResult[] = \Component\Streams\Service\StreamService::instance()->restore($row);
            }
            $result[$type] = $subResult;
        }
        return $result;
    }
    public static function getGroupedStreams($campaignIds)
    {
        $where = "campaign_id IN (" . implode(",", \Core\Db\Db::quote($campaignIds)) . ")";
        $where .= " AND state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        $streams = \Component\Streams\Repository\StreamRepository::instance()->rawRows("*", $where, "position");
        $groupedStreams = [];
        foreach ($streams as $stream) {
            $campaignId = $stream["campaign_id"];
            $type = $stream["type"];
            if (empty($groupedStreams[$campaignId][$type])) {
                $groupedStreams[$campaignId][$type] = [];
            }
            $groupedStreams[$campaignId][$type][] = $stream;
        }
        return $groupedStreams;
    }
    public static function cacheKey($campaignId)
    {
        return implode("_", [CACHE_KEY, $campaignId]);
    }
}

?>