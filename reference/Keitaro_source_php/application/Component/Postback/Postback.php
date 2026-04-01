<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback;

class Postback
{
    private $_params = NULL;
    private $_subId = NULL;
    private $_tid = NULL;
    private $_datetime = NULL;
    private $_status = NULL;
    private $_cost = NULL;
    private $_revenue = NULL;
    private $_originalStatus = NULL;
    private $_currency = NULL;
    private $_tidNames = ["tid", "transaction_id", "txid", "trans_id", "receipt"];
    private $_costNames = ["cost"];
    private $_payoutNames = ["revenue", "profit", "payout", "summ_approved", "payment", "amount", "order_sum", "order_amount", "order_sum", "affiliateCommission", "value"];
    private $_dateNames = ["conversion_time", "action_time", "postback_date", "datetime", "time", "action_time"];
    private $_statusNames = ["transactionType"];
    private $_statuses = ["sale" => ["approved", "1", "done", "confirm", "confirmed", "paid", "rebill", "sale", "signup", "payed", "awarded", "sell", "sms", "birj", "redeem", "cgbk", "insf", "test_sale"], "rejected" => ["failure", "reject", "cancelled", "refund", "cancel", "decline", "declined", "rejected", "invalid", "canceled", "trash"], "ignore" => ["unsubscribe", "subscribeOff", "off", "rfnd", "cancel-rebill", "test_rfnd", "cancel-test-rebill"]];
    private $_validFields = ["params", "sub_id", "tid", "datetime", "status", "cost", "revenue", "original_status", "currency"];
    const MAX_YEARS = 2;
    const MAX_SUB_IDS = 4;
    const MAX_EXTRA_PARAMS = 7;
    const LIMONBUCKS = "limonbucks";
    public function __construct($data = [])
    {
        $this->_datetime = new \DateTime();
        foreach ($data as $field => $value) {
            if (!in_array($field, $this->_validFields)) {
                throw new PostbackError("Invalid field \"" . $field . "\"");
            }
            $name = "_" . lcfirst(\Traffic\Tools\Tools::toCamelCase($field));
            $this->{$name} = $value;
        }
        if (!$this->_datetime instanceof \DateTime) {
            try {
                $this->_datetime = new \DateTime($this->_datetime);
            } catch (\Exception $e) {
                $this->_datetime = new \DateTime();
            }
        }
    }
    public static function buildFromParams($params)
    {
        $postback = new Postback([]);
        $postback->fromParams($params);
        return $postback;
    }
    public function fromParams($params)
    {
        $this->_params = $params;
        $this->_params = $this->_checkMcWap($this->_params);
        $this->_params = $this->_checkLimonbucks($this->_params);
        $this->_subId = $this->_filterSubId($this->_findSubId());
        $this->_findConversionDateTime();
        $this->_findTid();
        $this->_findRevenue();
        $this->_findStatus();
        $this->_findCost();
    }
    public function serialize()
    {
        return ["params" => $this->_params, "sub_id" => $this->_subId, "tid" => $this->_tid, "datetime" => $this->_datetime->format(\Core\Model\AbstractModel::DATETIME_FORMAT), "status" => $this->_status, "cost" => $this->_cost, "currency" => $this->_currency, "revenue" => $this->_revenue, "original_status" => $this->_originalStatus];
    }
    public function getSubId()
    {
        return $this->_subId;
    }
    public function setSubId($subId)
    {
        $this->_subId = $subId;
    }
    public function getTid()
    {
        return $this->_tid;
    }
    public function getParams()
    {
        return $this->_params;
    }
    public function getDateTime()
    {
        return $this->_datetime;
    }
    public function getStatus()
    {
        return $this->_status;
    }
    public function setStatus($status)
    {
        $this->_status = $status;
    }
    public function getOriginalStatus()
    {
        return $this->_originalStatus;
    }
    public function setRevenue($value)
    {
        $this->_revenue = $value;
    }
    public function getRevenue()
    {
        return $this->_revenue;
    }
    public function getRevenueCalculated(\Traffic\Model\Offer $offer = NULL)
    {
        if (!empty($offer) && !$offer->isPayoutAuto()) {
            return $this->_exchange($offer->getPayoutValue(), $offer->getPayoutCurrency());
        }
        return $this->_exchange($this->_revenue, $this->_currency);
    }
    public function getCost()
    {
        return $this->_cost;
    }
    public function hasCost()
    {
        return isset($this->_cost);
    }
    public function getCostCalculated()
    {
        return $this->_exchange($this->_cost, $this->_currency);
    }
    public function getCurrency()
    {
        return $this->_currency;
    }
    private function _exchange($value, $currency = NULL)
    {
        if (empty($currency)) {
            return $value;
        }
        return \Core\Currency\Service\CurrencyService::instance()->exchange($value, $currency, \Traffic\Repository\CachedSettingsRepository::instance()->get("currency"));
    }
    public function getSubIds()
    {
        $params = [];
        for ($i = 1; $i <= \Traffic\Model\Click::getSubIdCount(); $i++) {
            $name = "sub_id_" . $i;
            if (isset($this->_params[$name])) {
                $params[$name] = $this->_params[$name];
            }
        }
        return $params;
    }
    public function getExtraParams()
    {
        $params = [];
        for ($i = 1; $i <= \Traffic\Model\Click::EXTRA_PARAM_COUNT; $i++) {
            $name = "extra_param_" . $i;
            if (isset($this->_params[$name])) {
                $params[$name] = $this->_params[$name];
            }
        }
        return $params;
    }
    public function isSale()
    {
        return $this->_status == \Traffic\Model\Conversion::SALE;
    }
    public function isLead()
    {
        return $this->_status == \Traffic\Model\Conversion::LEAD;
    }
    public function isRejected()
    {
        return $this->_status == \Traffic\Model\Conversion::REJECTED;
    }
    public function isIgnore()
    {
        return $this->_status == \Traffic\Model\Conversion::IGNORE;
    }
    private function _filterSubId($subId)
    {
        if (strstr($subId, ":")) {
            $subIds = explode(":", $subId);
            $subId = $subIds[0];
            for ($i = 1; $i < count($subIds); $i++) {
                $this->_params["subid" . ($i + 1)] = $subIds[$i];
            }
        }
        if (preg_match("/^[0-9]+x[0-9]+x/", $subId)) {
            $subId = str_replace("x", "-", $subId);
        }
        return $subId;
    }
    private function _findSubId()
    {
        $names = \Component\Conversions\Service\ConversionsService::instance()->getSubIdAliases();
        foreach ($names as $name) {
            if (isset($this->_params[$name])) {
                $value = $this->_params[$name];
                return $value;
            }
        }
        return NULL;
    }
    private function _findRevenue()
    {
        if (!empty($this->_params["currency"])) {
            $this->_currency = $this->_params["currency"];
        }
        foreach ($this->_payoutNames as $name) {
            if (isset($this->_params[$name])) {
                $value = $this->_params[$name];
                $value = str_replace(",", ".", $value);
                $this->_revenue = (int) $value;
                return NULL;
            }
        }
        $this->_revenue = 0;
    }
    private function _findTid()
    {
        foreach ($this->_tidNames as $name) {
            if (isset($this->_params[$name])) {
                $value = $this->_params[$name];
                $this->_tid = $value;
                return NULL;
            }
        }
    }
    private function _findCost()
    {
        foreach ($this->_costNames as $name) {
            if (isset($this->_params[$name])) {
                $value = $this->_params[$name];
                $value = str_replace(",", ".", $value);
                $this->_cost = $value;
                return NULL;
            }
        }
    }
    private function _findStatus()
    {
        $this->_status = \Traffic\Model\Conversion::LEAD;
        $this->_originalStatus = empty($this->_params["status"]) ? NULL : $this->_params["status"];
        if (empty($this->_params["status"])) {
            $this->_status = \Traffic\Model\Conversion::SALE;
        }
        if (isset($this->_params["sale_status"])) {
            $statuses = urldecode($this->_params["sale_status"]);
            $statuses = explode(",", strtolower($statuses));
            $this->_statuses[\Traffic\Model\Conversion::SALE] = $statuses;
        }
        if (isset($this->_params["lead_status"])) {
            $statuses = urldecode($this->_params["lead_status"]);
            $statuses = explode(",", strtolower($statuses));
            $this->_statuses[\Traffic\Model\Conversion::LEAD] = $statuses;
        }
        if (isset($this->_params["ignore_status"])) {
            $statuses = urldecode($this->_params["ignore_status"]);
            $statuses = explode(",", strtolower($statuses));
            $this->_statuses[\Traffic\Model\Conversion::IGNORE] = $statuses;
        }
        if (isset($this->_params["rejected_status"])) {
            $statuses = urldecode($this->_params["rejected_status"]);
            $statuses = explode(",", strtolower($statuses));
            $this->_statuses[\Traffic\Model\Conversion::REJECTED] = $statuses;
        }
        if (isset($this->_params["status"])) {
            foreach ($this->_statuses as $status => $variations) {
                if (in_array(strtolower($this->_params["status"]), $variations)) {
                    $this->_status = $status;
                }
            }
        }
    }
    private function _findConversionDateTime()
    {
        $this->_datetime = new \DateTime();
        foreach ($this->_dateNames as $name) {
            if (isset($this->_params[$name])) {
                if (is_numeric($this->_params[$name])) {
                    $customDate = new \DateTime();
                    $customDate->setTimestamp(strtotime($this->_params[$name]));
                    return NULL;
                }
                try {
                    $customDate = new \DateTime($this->_params[$name], \Component\Users\Repository\UserRepository::instance()->getFirstAdminTimezone());
                } catch (\Exception $e) {
                    if (!empty($customDate) && $customDate->format("Y") - $this->_datetime->format("Y") <= MAX_YEARS) {
                        $this->_datetime = $customDate;
                    }
                }
            }
        }
    }
    private function _checkMcWap($params)
    {
        if (isset($params["mcwap"]) && is_array($params["mcwap"])) {
            $params["payout"] = $params["mcwap"]["income"];
            $params["status"] = $params["mcwap"]["action_name"];
            unset($params["mcwap"]["income"]);
            unset($params["mcwap"]["action_name"]);
            $params = array_merge($params, $params["mcwap"]);
            unset($params["mcwap"]);
        }
        return $params;
    }
    private function _checkLimonbucks($params)
    {
        if (isset($params["from"]) && $params["from"] == LIMONBUCKS) {
            $params["payout"] = $params["cost"];
            unset($params["cost"]);
        }
        return $params;
    }
}

?>