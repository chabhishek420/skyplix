<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Serializer;

class DomainSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    protected $_campaignDic = NULL;
    private $_domainErrorsService = NULL;
    public function __construct()
    {
        $this->_campaignDic = \Component\Campaigns\Repository\CampaignRepository::instance()->getDomainCountsDic();
        $this->_domainErrorsService = new \Component\Domains\Service\DomainErrorsService();
    }
    public function extra($obj, $data)
    {
        $data = $this->_flatTimestamps($data);
        $data["campaigns_count"] = 0;
        if (isset($this->_campaignDic[$data["id"]])) {
            $data["campaigns_count"] = $this->_campaignDic[$data["id"]];
        }
        if (mb_substr($data["name"], 0, 4, "utf-8") === "xn--") {
            $puny = new \TrueBV\Punycode();
            $data["name"] = $puny->decode($data["name"]);
        }
        $data["default_campaign"] = "";
        if (!empty($data["default_campaign_id"])) {
            try {
                $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($data["default_campaign_id"]);
            } catch (\Core\Exceptions\NotFoundError $e) {
                if (!empty($campaign)) {
                    $data["default_campaign"] = $campaign->getName();
                }
            }
        }
        $data["error_solution"] = "";
        if (!empty($data["error_description"])) {
            $data["error_solution"] = $this->_domainErrorsService->getErrorSolution($data["error_description"]);
        }
        return $data;
    }
}

?>