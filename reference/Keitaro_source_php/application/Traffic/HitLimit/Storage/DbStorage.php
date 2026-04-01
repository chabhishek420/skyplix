<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\HitLimit\Storage;

class DbStorage implements StorageInterface
{
    const FILTER_NAME = "limit";
    public function store(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
        \Traffic\HitLimit\Service\HitRequestService::instance()->create(["stream_id" => $stream->getId(), "datetime" => $currentDateTime->format(\Core\Model\AbstractModel::DATETIME_FORMAT)]);
    }
    public function perHour(\Traffic\Model\BaseStream $stream, \DateTime $currentDateTime)
    {
        $where = [];
        $where[] = "stream_id = " . \Core\Db\Db::quote($stream->getId());
        $where[] = "datetime >= DATE_SUB(" . \Core\Db\Db::quote($currentDateTime->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ", INTERVAL 1 HOUR)";
        return \Traffic\HitLimit\Repository\HitRequestRepository::instance()->count(implode(" AND ", $where));
    }
    public function perDay(\Traffic\Model\BaseStream $stream, \DateTime $date)
    {
        $where = [];
        $where[] = "stream_id = " . \Core\Db\Db::quote($stream->getId());
        $where[] = "datetime >= DATE_SUB(" . \Core\Db\Db::quote($date->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ", INTERVAL 1 DAY)";
        return \Traffic\HitLimit\Repository\HitRequestRepository::instance()->count(implode(" AND ", $where));
    }
    public function total(\Traffic\Model\BaseStream $stream)
    {
        return \Traffic\HitLimit\Repository\HitRequestRepository::instance()->count("stream_id = " . \Core\Db\Db::quote($stream->getId()));
    }
    public function prune(\DateTime $currentDateTime)
    {
        $exceptions = $this->_getStreamIdsWithLimitTotal();
        $where = "datetime < DATE_SUB(" . \Core\Db\Db::quote($currentDateTime->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ", INTERVAL 1 DAY)";
        if (count($exceptions)) {
            $where .= " AND stream_id NOT IN (" . implode(",", $exceptions) . ")";
        }
        \Traffic\HitLimit\Service\HitRequestService::instance()->directDeleteAll($where);
    }
    private function _getStreamIdsWithLimitTotal()
    {
        $where = "name = " . \Core\Db\Db::quote(FILTER_NAME);
        $ids = [];
        $items = \Component\StreamFilters\Repository\StreamFilterRepository::instance()->all($where);
        foreach ($items as $filter) {
            $payload = $filter->getPayload();
            if (!empty($payload["total"])) {
                $ids[] = $filter->getStreamId();
            }
        }
        return $ids;
    }
}

?>