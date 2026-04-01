<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Repository;

class ReportRepository extends \Traffic\Service\AbstractService
{
    public function get($params, \Component\Grid\QueryParams\UserParams $userParams, \Component\Reports\Grid\ReportDefinition $definition = NULL)
    {
        if (empty($definition)) {
            $definition = new \Component\Reports\Grid\ReportDefinition();
        }
        $queryParams = new \Component\Grid\QueryParams\QueryParams($params, $definition);
        $builder = \Component\Grid\Builder\GridBuilder::factory($queryParams, $userParams);
        return $builder->build();
    }
    public function briefCampaignStats(\Traffic\Model\Campaign $campaign, \Component\Grid\QueryParams\UserParams $userParams, $range)
    {
        $result = new \stdClass();
        $params = ["range" => $range, "metrics" => ["clicks", "stream_unique_clicks", "bots"], "grouping" => ["stream_id"], "filters" => [["name" => "campaign_id", "operator" => "equals", "expression" => $campaign->getId()]]];
        $queryParams = new \Component\Grid\QueryParams\QueryParams($params, new \Component\Reports\Grid\ReportDefinition());
        $builder = \Component\Grid\Builder\GridBuilder::factory($queryParams, $userParams);
        $reportResult = $builder->build();
        if (empty($reportResult["rows"])) {
            $result->null = ["clicks" => 0, "stream_unique_clicks" => 0, "bots" => 0];
            return $result;
        }
        foreach ($reportResult["rows"] as $row) {
            if (!empty($row["stream_id"])) {
                $result->{$row["stream_id"]} = ["clicks" => (int) $row["clicks"], "stream_unique_clicks" => (int) $row["stream_unique_clicks"], "bots" => (int) $row["bots"]];
            }
        }
        return $result;
    }
}

?>