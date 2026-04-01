<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Service;

class TrafficSourceService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\TrafficSource::definition();
    }
    public function getParameterAliases(\Traffic\Model\TrafficSource $ts)
    {
        $result = [];
        $tsParams = $ts->getParameters();
        if ($tsParams && count($tsParams)) {
            foreach ($tsParams as $parameter => $data) {
                $alias = $this->getAliasForParameter($tsParams, $parameter);
                if (!empty($alias)) {
                    $result[] = ["parameter" => $parameter, "alias" => $alias];
                }
            }
        }
        return $result;
    }
    public function getAliasForParameter($parameters, $paramName)
    {
        $prefix = "";
        if (preg_match("/sub_id_([0-9]+)/", $paramName, $result)) {
            $prefix = "[S" . $result[1] . "] ";
        }
        if (preg_match("/extra_param_([0-9]+)/", $paramName, $result)) {
            $prefix = "[X" . $result[1] . "] ";
        }
        if ($parameters && isset($parameters[$paramName]) && !empty($parameters[$paramName]["alias"])) {
            return $prefix . $parameters[$paramName]["alias"];
        }
        return NULL;
    }
    public function archiveTrafficSource(\Core\Entity\Model\EntityModelInterface $ts)
    {
        $this->archive($ts);
        \Component\Campaigns\Service\CampaignService::instance()->unbindTrafficSource($ts->getId());
    }
    public function updateTrafficSource(\Core\Entity\Model\EntityModelInterface $ts, $data)
    {
        $updateInCampaigns = isset($data["update_in_campaigns"]) ? (int) $data["update_in_campaigns"] : false;
        $ts = $this->update($ts, $data);
        if ($updateInCampaigns) {
            $updateParams = ["traffic_loss" => $ts->getTrafficLoss(), "parameters" => $ts->getParameters()];
            if (!empty($updateParams)) {
                $where = "traffic_source_id = " . \Core\Db\Db::quote($ts->getId());
                \Component\Campaigns\Service\CampaignService::instance()->updateMany($where, $updateParams);
            }
        }
        return $ts;
    }
}

?>