<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Repository;

class ClickRepository extends \Traffic\Service\AbstractService
{
    public function all()
    {
        return \Core\Db\DataRepository::instance()->all(\Traffic\Model\Click::definition());
    }
    public function count(\Datetime $start = NULL, \Datetime $end = NULL, \Traffic\Model\Campaign $campaign = NULL, $onlyCampaignUniques = NULL)
    {
        $where = [];
        if ($start) {
            $where[] = "campaign_id = " . \Core\Db\Db::quote($campaign->getId());
            $where[] = "(`datetime` BETWEEN " . \Core\Db\Db::quote($start->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . " AND " . \Core\Db\Db::quote($end->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ")";
            if ($onlyCampaignUniques) {
                $where[] = "is_unique_campaign = 1";
            }
        }
        return \Core\Db\DataRepository::instance()->getOne(\Traffic\Model\Click::definition(), "COUNT(*)", implode(" AND ", $where));
    }
    public function log($params, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $queryParams = new \Component\Grid\QueryParams\QueryParams($params, new \Component\Clicks\Grid\ClickLogDefinition());
        $where = NULL;
        $builder = \Component\Grid\Builder\GridBuilder::factory($queryParams, $userParams);
        return $builder->build();
    }
    public function summary($params, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $queryParams = new \Component\Grid\QueryParams\QueryParams($params, new \Component\Reports\Grid\ReportDefinition());
        $builder = \Component\Grid\Builder\GridBuilder::factory($queryParams, $userParams);
        return $builder->getSummary();
    }
    public function findByPostback(\Component\Postback\Postback $postback)
    {
        $subId = \Core\Db\Db::quote($postback->getSubId());
        $where = "sub_id = " . $subId . " OR parent_sub_id = " . $subId;
        $clickLinks = \Core\Db\DataRepository::instance()->all(\Component\Clicks\Model\ClickLink::definition(), $where);
        $linkSubIds = [];
        if (!empty($clickLinks)) {
            $linkSubIds[] = $subId;
            foreach ($clickLinks as $link) {
                $linkSubIds[] = \Core\Db\Db::quote($link->getSubId());
                $linkSubIds[] = \Core\Db\Db::quote($link->getParentSubId());
            }
            $linkSubIds = array_unique($linkSubIds);
        } else {
            $linkSubIds = [$subId];
        }
        return \Core\Db\DataRepository::instance()->all(\Traffic\Model\Click::definition(), "sub_id in (" . implode(",", $linkSubIds) . ")");
    }
}

?>