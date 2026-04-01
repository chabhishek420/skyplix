<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Click extends \Core\Model\AbstractModel
{
    protected static $_fields = NULL;
    protected static $_tableName = "clicks";
    protected static $_primaryKey = "click_id";
    const EXTRA_PARAM_COUNT = 10;
    const SUB_ID_COUNT = 15;
    public static function getSubIdCount()
    {
        if (\Traffic\Repository\ParameterRepository::instance()->hasSubId15()) {
            return 15;
        }
        return 10;
    }
    public static function service()
    {
        return \Component\Clicks\Service\ClickService::instance();
    }
    public function getSubId()
    {
        return $this->get("sub_id");
    }
    public function getStreamId()
    {
        return $this->get("stream_id");
    }
    public function getDateTime()
    {
        return $this->get("datetime");
    }
    public function lpClick($data)
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
    public function getCost()
    {
        return $this->get("cost");
    }
    public function addRevenue($revenueField, $revenue)
    {
        $newRevenue = $this->get($revenueField) + $revenue;
        $this->set($revenueField, $newRevenue);
        return $this;
    }
    public function subtractRevenue($revenueField, $revenue)
    {
        $newRevenue = $this->get($revenueField) - $revenue;
        if ($newRevenue < 0) {
            $newRevenue = 0;
        }
        $this->set($revenueField, $newRevenue);
        return $this;
    }
    public function getRevenue()
    {
        return $this->get("lead_revenue") + $this->get("sale_revenue") + $this->get("rejected_revenue");
    }
    public function isLead()
    {
        return $this->get("is_lead");
    }
    public function isSale()
    {
        return $this->get("is_sale");
    }
    public function isRejected()
    {
        return $this->get("is_rejected");
    }
    public function getOfferId()
    {
        return $this->get("offer_id");
    }
    public function getRebills()
    {
        return $this->get("rebills");
    }
    public function getLandingClicked()
    {
        return $this->get("landing_clicked");
    }
    public function getLandingClickedDatetime()
    {
        return $this->get("landing_clicked_datetime");
    }
    public function getLandingId()
    {
        return $this->get("landing_id");
    }
    public function getCampaignId()
    {
        return $this->get("campaign_id");
    }
}

?>