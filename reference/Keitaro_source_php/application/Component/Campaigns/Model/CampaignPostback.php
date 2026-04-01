<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Model;

class CampaignPostback extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "campaign_postbacks";
    public static function validator()
    {
        new \Component\Campaigns\Validator\CampaignPostbackValidator();
    }
    public static function repository()
    {
        return \Component\Campaigns\Repository\CampaignPostbackRepository::instance();
    }
    public static function service()
    {
        return \Component\Campaigns\Service\CampaignPostbackService::instance();
    }
    public function setCampaign(\Traffic\Model\Campaign $campaign)
    {
        $this->set("campaign_id", $campaign->getId());
        return $this;
    }
    public function getUrl()
    {
        return $this->get("url");
    }
    public function getSource()
    {
        return $this->get("source");
    }
    public function getStatuses()
    {
        return $this->get("statuses");
    }
    public function getMethod()
    {
        return $this->get("method");
    }
    public static function deleteByCampaign(\Traffic\Model\Campaign $campaign, $exclude = NULL)
    {
        if (!count($exclude)) {
            $exclude = [-1];
        }
        $where = "campaign_id = " . \Core\Db\Db::quote($campaign->getId()) . " AND id NOT IN (" . implode(",", $exclude) . ")";
        \Component\Campaigns\Service\CampaignPostbackService::instance()->directDeleteAll($where);
    }
}

?>