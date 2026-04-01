<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Conversion extends \Core\Model\AbstractModel
{
    protected static $_fields = NULL;
    protected static $_tableName = "conversions_2";
    protected static $_primaryKey = "conversion_id";
    const LEAD = "lead";
    const SALE = "sale";
    const REJECTED = "rejected";
    const REBILL = "rebill";
    const IGNORE = "ignore";
    public static function validator()
    {
        new \Component\Conversions\Validator\ConversionValidator();
    }
    public static function service()
    {
        return \Component\Conversions\Service\ConversionService::instance();
    }
    public function isLead()
    {
        return $this->getStatus() === LEAD;
    }
    public function isSale()
    {
        return $this->getStatus() === SALE;
    }
    public function isRejected()
    {
        return $this->getStatus() === REJECTED;
    }
    public function isRebill()
    {
        return $this->getStatus() === REBILL;
    }
    public function isChangeToRejected()
    {
        return $this->getStatus() === REJECTED && $this->getPreviousStatus() !== REJECTED;
    }
    public function isChangeFromRejected()
    {
        return $this->getStatus() !== REJECTED && $this->getPreviousStatus() === REJECTED;
    }
    public function isPreviousLead()
    {
        return $this->getPreviousStatus() === LEAD;
    }
    public function isPreviousSale()
    {
        return $this->getPreviousStatus() === SALE;
    }
    public function isPreviousRejected()
    {
        return $this->getPreviousStatus() === REJECTED;
    }
    public function isPreviousRebill()
    {
        return $this->getPreviousStatus() === REBILL;
    }
    public function setCost($cost)
    {
        $this->set("cost", $cost);
        return $this;
    }
    public function getCost()
    {
        return $this->get("cost");
    }
    public function setRevenue($value)
    {
        $this->set("revenue", $value);
        return $this;
    }
    public function getRevenue()
    {
        return $this->get("revenue");
    }
    public function getProfit()
    {
        return $this->get("revenue") - $this->get("cost");
    }
    public function getParams()
    {
        return $this->get("params");
    }
    public function getTid()
    {
        return $this->get("tid");
    }
    public function getSubId()
    {
        return $this->get("sub_id");
    }
    public function getStatus()
    {
        return $this->get("status");
    }
    public function getPreviousStatus()
    {
        return $this->get("previous_status");
    }
    public function getCampaignId()
    {
        return $this->get("campaign_id");
    }
    public function getStreamId()
    {
        return $this->get("stream_id");
    }
    public function getClickDateTime()
    {
        return $this->get("click_datetime");
    }
    public function isProcessed()
    {
        return $this->get("is_processed");
    }
    public function isConvertedLead()
    {
        return $this->getPreviousStatus() == "lead" && $this->getStatus() == "sale";
    }
    public function getOfferId(Conversion $conversion)
    {
        return $this->get("offer_id");
    }
    public function setProcessed($value)
    {
        $this->set("is_processed", $value);
        return $this;
    }
    public function getOriginalStatus()
    {
        return $this->get("original_status");
    }
    public function getPostbackDatetime()
    {
        return $this->get("postback_datetime");
    }
    public function getSaleDateTime()
    {
        return $this->get("sale_datetime");
    }
}

?>