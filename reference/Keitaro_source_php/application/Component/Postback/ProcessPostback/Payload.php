<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback;

class Payload
{
    private $_postback = NULL;
    private $_click = NULL;
    private $_oldConversions = NULL;
    private $_selectedOldConversion = NULL;
    private $_newConversion = NULL;
    private $_offer = NULL;
    private $_hasTid = NULL;
    public function __construct(\Component\Postback\Postback $postback, \Traffic\Model\Click $click = NULL, \Traffic\Model\Conversion $conversion = NULL)
    {
        $this->_postback = $postback;
        $this->_click = $click;
        $this->_oldConversions = [];
        $this->_newConversion = $conversion;
    }
    public function isSale()
    {
        return $this->_postback->getStatus() === \Traffic\Model\Conversion::SALE;
    }
    public function isRejected()
    {
        return $this->_postback->getStatus() === \Traffic\Model\Conversion::REJECTED;
    }
    public function hasTid()
    {
        return $this->_hasTid;
    }
    public function getPostback()
    {
        return $this->_postback;
    }
    public function getClickInfo()
    {
        "Stream ID: " . $this->_click->getStreamId() ? exit : "no";
    }
    public function getClick()
    {
        return $this->_click;
    }
    public function addOldConversion(\Traffic\Model\Conversion $conversion)
    {
        $this->_oldConversions[] = $conversion;
        return $this;
    }
    public function updateOldConversionsTid()
    {
        $this->_hasTid = false;
        foreach ($this->_oldConversions as $old) {
            if ($old->getTid() == $this->_postback->getTid()) {
                $this->_hasTid = true;
                $this->_selectedOldConversion = $old;
            }
        }
    }
    public function checkOldConversionsHasSale()
    {
        foreach ($this->_oldConversions as $old) {
            if ($old->isSale() || $old->isRebill()) {
                return true;
            }
        }
        return false;
    }
    public function setNewConversion(\Traffic\Model\Conversion $conversion)
    {
        $this->_newConversion = $conversion;
        return $this;
    }
    public function getNewConversion()
    {
        return $this->_newConversion;
    }
    public function getConversionToSave()
    {
        if ($this->_newConversion) {
            return $this->_newConversion;
        }
        return $this->_selectedOldConversion;
    }
    private function getAllConversions()
    {
        $conversions = $this->_oldConversions;
        if ($this->_newConversion) {
            $conversions[] = $this->_newConversion;
        }
        return $conversions;
    }
    public function getTotalLeadRevenue()
    {
        $result = 0;
        $conversions = $this->getAllConversions();
        foreach ($conversions as $conversion) {
            if ($conversion->isLead()) {
                $result += $conversion->getRevenue();
            }
        }
        return $result;
    }
    public function getTotalSaleRevenue()
    {
        $result = 0;
        $conversions = $this->getAllConversions();
        foreach ($conversions as $conversion) {
            if ($conversion->isSale() || $conversion->isRebill()) {
                $result += $conversion->getRevenue();
            }
        }
        return $result;
    }
    public function getTotalRejectedRevenue()
    {
        $result = 0;
        $conversions = $this->getAllConversions();
        foreach ($conversions as $conversion) {
            if ($conversion->isRejected()) {
                $result += $conversion->getRevenue();
            }
        }
        return $result;
    }
    public function setOffer(\Traffic\Model\Offer $offer)
    {
        $this->_offer = $offer;
        return $this;
    }
    public function getOffer()
    {
        return $this->_offer;
    }
    public function isNewConversion()
    {
        return empty($this->_oldConversions);
    }
    public function isStatusChange()
    {
        return !empty($this->_oldConversions) && $this->_hasTid || $this->_postback->isIgnore();
    }
    public function isAdditionalConversion()
    {
        return !empty($this->_oldConversions) && !$this->_hasTid;
    }
    public function alreadyHasSale()
    {
        foreach ($this->_oldConversions as $conversion) {
            if ($conversion->isSale() || $conversion->isRebill()) {
                return true;
            }
        }
        return false;
    }
    public function haltOnOfferDisallowRebill()
    {
        if ($this->isAdditionalConversion() && $this->alreadyHasSale() && $this->_offer && !$this->_offer->get("payout_upsell")) {
            throw new \Component\Postback\PostbackError("Conversion is already exists. Postback ignored.");
        }
    }
    public function restoreRejectedRevenue()
    {
        if ($this->_hasTid) {
            return $this->_selectedOldConversion->getRevenue();
        }
        return 0;
    }
}

?>