<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Repository;

class ConversionRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\Conversion::definition();
    }
    public function fetchRow($select = "*", $where = NULL)
    {
        return \Core\Db\DataRepository::instance()->fetchRow(\Traffic\Model\Conversion::definition(), $select, $where);
    }
    public function log($params, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $params["grouping"] = ["conversion_id"];
        $queryParams = new \Component\Grid\QueryParams\QueryParams($params, new \Component\Conversions\Grid\ConversionsLogDefinition());
        $builder = \Component\Grid\Builder\GridBuilder::factory($queryParams, $userParams);
        return $builder->build();
    }
    public function findBySubIds($subIds)
    {
        $subId = \Core\Db\Db::quote($subId);
        $result = [];
        if (!empty($subIds)) {
            $where = "sub_id IN (" . implode(",", $subIds) . ")";
            $conversions = \Core\Db\DataRepository::instance()->all(\Traffic\Model\Conversion::definition(), $where);
            foreach ($conversions as $conversion) {
                $result[] = $conversion;
            }
        }
        return $result;
    }
    public function getStatuses()
    {
        $items = [\Traffic\Model\Conversion::LEAD, \Traffic\Model\Conversion::SALE, \Traffic\Model\Conversion::REJECTED, \Traffic\Model\Conversion::REBILL];
        $result = [];
        foreach ($items as $item) {
            $result[] = ["id" => $item, "name" => \Core\Locale\LocaleService::t("conversions.statuses." . $item)];
        }
        return $result;
    }
}

?>