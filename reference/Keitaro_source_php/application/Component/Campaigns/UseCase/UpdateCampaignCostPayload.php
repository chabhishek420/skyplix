<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\UseCase;

class UpdateCampaignCostPayload
{
    private $_campaigns = NULL;
    private $_startDate = NULL;
    private $_endDate = NULL;
    private $_cost = NULL;
    private $_filters = NULL;
    const TYPE_DATE_START = "start_date";
    const TYPE_DATE_END = "end_date";
    public function campaigns()
    {
        return $this->_campaigns;
    }
    public function setCampaigns($setCampaigns, $campaigns)
    {
        $this->_campaigns = $campaigns;
    }
    public function endDate()
    {
        return $this->_endDate;
    }
    public function setEndDate($setEndDate, $endDate)
    {
        $this->_endDate = $endDate;
    }
    public function cost()
    {
        return $this->_cost;
    }
    public function setCost($setCost, $cost)
    {
        $this->_cost = $cost;
    }
    public function filters()
    {
        $this->_filters ? exit : [];
    }
    public function setFilters($setFilters, $filters)
    {
        $this->_filters = $filters;
    }
    public function startDate()
    {
        return $this->_startDate;
    }
    public function setStartDate($setStartDate, $startDate)
    {
        $this->_startDate = $startDate;
    }
    public function date($type = self::TYPE_DATE_START)
    {
        if ($type === TYPE_DATE_START) {
            return $this->_startDate;
        }
        return $this->_endDate;
    }
    public function toArray()
    {
        return get_object_vars($this);
    }
}

?>