<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\UseCase;

class UpdateCampaignCostBulk
{
    private $_currency = NULL;
    private $_timezone = NULL;
    private $_onlyCampaignUniques = NULL;
    private $_definition = NULL;
    private $_data = NULL;
    const DATE_COLUMN_CLICK = "datetime";
    const DATE_COLUMN_CONVERSION = "click_datetime";
    const TYPE_CLICK = "click";
    const TYPE_CONVERSION = "conversion";
    public function __construct()
    {
        $this->_definition = new \Component\Conversions\Grid\ConversionsLogDefinition();
    }
    public function setData($data)
    {
        $this->_data = $data;
        return $this;
    }
    public function setCurrency($currency)
    {
        $this->_currency = $currency;
        return $this;
    }
    public function setTimezone($timezone)
    {
        $this->_timezone = $timezone;
        return $this;
    }
    public function getTimezone()
    {
        $this->_timezone ? exit : "UTC";
    }
    private function _getRange($range)
    {
        if (!$range[0] instanceof \DateTime) {
            $range[0] = new \DateTime($range[0], new \DateTimeZone($this->getTimezone()));
            $range[0]->setTimeZone(new \DateTimeZone("UTC"));
        }
        if (!$range[1] instanceof \DateTime) {
            $range[1] = new \DateTime($range[1], new \DateTimeZone($this->getTimezone()));
            $range[1]->setTimeZone(new \DateTimeZone("UTC"));
        }
        return $range;
    }
    public function onlyCampaignUniques($status)
    {
        $this->_onlyCampaignUniques = $status;
        return $this;
    }
    public function update()
    {
        $this->_updateClicksCost();
        $this->_updateConversionsCost();
    }
    private function _getCostPerClick($filters, $range, $cost)
    {
        $newCost = $cost;
        $count = $this->_getClicksCount($filters, $range);
        \Traffic\Logging\Service\LoggerService::instance()->info("[UpdateCampaignCost] Found " . $count . " clicks for that period");
        if (!$count) {
            return 0;
        }
        if ($this->_currency) {
            $newCost = \Core\Currency\Service\CurrencyService::instance()->exchange($cost, $this->_currency, \Traffic\Repository\CachedSettingsRepository::instance()->get("currency"));
        }
        $costPerClick = $newCost / $count;
        \Traffic\Logging\Service\LoggerService::instance()->info("[UpdateCampaignCost] Found " . $count . " clicks for that period, CPC: " . $costPerClick);
        return $costPerClick;
    }
    private function _getClicksCount($filters, $range)
    {
        $where = $this->_getClicksWhere($filters, $range);
        $joins = $this->_getJoins();
        $sql = "SELECT COUNT(*) FROM " . \Traffic\Model\Click::getTableName() . " t_conversions_2 " . $joins . " " . $where;
        return \Core\Db\Db::instance()->getOne($sql);
    }
    private function _getFilterNames()
    {
        $filters = [];
        if (!empty($this->_data)) {
            foreach ($this->_data as $item) {
                $filters = array_merge($filters, array_keys($item->filters()));
            }
        }
        return $filters;
    }
    private function _getAllFilters()
    {
        $filters = [];
        if (!empty($this->_data)) {
            foreach ($this->_data as $item) {
                foreach ($item->filters() as $key => $filter) {
                    if (!is_array($filter)) {
                        $filter = explode(",", $filter);
                        array_walk($filter, function ($val) {
                            $val = trim($val);
                        });
                    }
                    if (!empty($filters[$key])) {
                        $filters[$key] = array_unique(array_merge($filters[$key], $filter));
                    } else {
                        $filters[$key] = $filter;
                    }
                }
            }
        }
        return $filters;
    }
    private function _getAllCampaigns()
    {
        $campaigns = [];
        if (!empty($this->_data)) {
            foreach ($this->_data as $item) {
                foreach ($item->campaigns() as $campaign) {
                    if (!in_array($campaign, $campaigns)) {
                        $campaigns[] = $campaign;
                    }
                }
            }
        }
        return $campaigns;
    }
    private function _getJoins()
    {
        $join = new \Component\Grid\Query\Joins($this->_getFilterNames(), $this->_definition);
        return $join->getSql();
    }
    private function _getRawFilter($filters)
    {
        if (empty($filters)) {
            return [];
        }
        $rawFilters = [];
        foreach ($filters as $name => $expression) {
            if (!is_array($expression)) {
                $expression = explode(",", $expression);
                array_walk($expression, function ($val) {
                    $val = trim($val);
                });
            }
            $rawFilters[] = [\Component\Grid\Query\FilterItem::NAME => $name, \Component\Grid\Query\FilterItem::OPERATOR => \Component\Grid\Query\FilterItem::IN_LIST, \Component\Grid\Query\FilterItem::EXPRESSION => $expression];
        }
        return $rawFilters;
    }
    private function _getJoinFilters($filters)
    {
        $filter = new \Component\Grid\Query\Filter($this->_getRawFilter($filters), $this->_definition);
        $sql = $filter->getWhereSql();
        if (empty($sql)) {
            $sql = " WHERE 1 ";
        } else {
            $sql = $this->_updateQueryToORCondition($sql);
        }
        return $sql;
    }
    private function _updateQueryToORCondition($sql)
    {
        $newSql = str_replace("AND", "OR", $sql);
        return str_replace("WHERE ", "WHERE (", $newSql) . ")";
    }
    private function _getUpdateSql($type = self::TYPE_CLICK)
    {
        $where = $this->_getWhereBulk($type);
        $joins = $this->_getJoins();
        $case = $this->_getCase($type);
        $table = \Traffic\Model\Click::getTableName();
        if ($type === TYPE_CONVERSION) {
            $table = \Traffic\Model\Conversion::getTableName();
        }
        return "UPDATE " . $table . " t_conversions_2 " . $joins . " SET cost = CASE " . $case . " END " . implode(" AND ", $where);
    }
    private function _updateConversionsCost()
    {
        $sql = $this->_getUpdateSql(TYPE_CONVERSION);
        \Core\Db\Db::instance()->execute($sql);
    }
    private function _updateClicksCost()
    {
        $sql = $this->_getUpdateSql();
        \Core\Db\Db::instance()->execute($sql);
    }
    private function _getClicksWhere($filters, $range)
    {
        $where = [$this->_getJoinFilters($filters)];
        $where = array_merge($where, $this->_getClickMainFilters($range, $this->_getAllCampaigns()));
        return implode(" AND ", $where);
    }
    private function _getCase($type = self::TYPE_CLICK)
    {
        $case = [];
        foreach ($this->_data as $item) {
            $where = [$this->_getJoinFilters($item->filters())];
            $range = $this->_getRange([$item->startDate(), $item->endDate()]);
            $where = array_merge($where, $this->_getClickMainFilters($range, $item->campaigns(), $type));
            $perClick = $this->_getCostPerClick($item->filters(), $range, $item->cost());
            $query = implode(" AND ", $where) . " THEN " . $perClick;
            $case[] = str_replace("WHERE", "WHEN", $query);
        }
        return implode(" ", $case);
    }
    private function _getClickMainFilters($range, $campaigns, $type = self::TYPE_CLICK)
    {
        $dateColumn = $this->_getDateColumnName($type);
        $where = [];
        $where[] = "campaign_id IN (" . implode(",", \Core\Db\Db::quote($campaigns)) . ")";
        $where[] = "(" . $dateColumn . " BETWEEN " . \Core\Db\Db::quote($range[0]->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . " AND " . \Core\Db\Db::quote($range[1]->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ")";
        if ($this->_onlyCampaignUniques && $type === TYPE_CLICK) {
            $where[] = "is_unique_campaign = 1";
        }
        return $where;
    }
    private function _getWhereBulk($type = self::TYPE_CLICK)
    {
        $dateColumn = $this->_getDateColumnName($type);
        $where = [];
        $where[] = $this->_getJoinFilters($this->_getAllFilters());
        $where[] = "campaign_id IN (" . implode(",", \Core\Db\Db::quote($this->_getAllCampaigns())) . ")";
        $where[] = "(" . $dateColumn . " BETWEEN " . \Core\Db\Db::quote($this->_getStartRangeFromData()->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . " AND " . \Core\Db\Db::quote($this->_getEndRangeFromData()->format(\Core\Model\AbstractModel::DATETIME_FORMAT)) . ")";
        if ($this->_onlyCampaignUniques && $type === TYPE_CLICK) {
            $where[] = "is_unique_campaign = 1";
        }
        return $where;
    }
    private function _getDateColumnName($type)
    {
        if ($type === TYPE_CONVERSION) {
            return DATE_COLUMN_CONVERSION;
        }
        return DATE_COLUMN_CLICK;
    }
    private function _getDateRange($date)
    {
        $dates = [];
        if (!empty($this->_data)) {
            foreach ($this->_data as $item) {
                $dates[] = $item->date($date);
            }
        }
        return $dates;
    }
    private function _getStartRangeFromData()
    {
        $date = min($this->_getDateRange("start_date"));
        $date = new \DateTime($date, new \DateTimeZone($this->getTimezone()));
        $date->setTimeZone(new \DateTimeZone("UTC"));
        return $date;
    }
    private function _getEndRangeFromData()
    {
        $date = max($this->_getDateRange("end_date"));
        $date = new \DateTime($date, new \DateTimeZone($this->getTimezone()));
        $date->setTimeZone(new \DateTimeZone("UTC"));
        return $date;
    }
}

?>