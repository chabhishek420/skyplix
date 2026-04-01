<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Grid;

class ConversionsLogDefinition extends \Component\Clicks\Grid\ClickLogDefinition
{
    protected $_table = "conversions_2";
    protected $_url = "?object=conversions.log";
    protected $_serializer = "ConversionSerializer";
    protected $_rangeTimeField = "postback_datetime";
    protected $_details = ["id" => "conversion_id"];
    public function initColumns()
    {
        self::initColumns();
        $removeColumns = ["profitability", "click_id", "time", "datetime", "is_unique_stream", "is_unique_campaign", "is_unique_global", "is_bot", "is_using_proxy", "destination", "sale_revenue", "lead_revenue", "rejected_revenue", "is_lead", "is_sale", "is_rejected", "rebills", "profit", "external", "creative", "parent_campaign_id", "parent_campaign", "landing_clicked", "landing_clicked_datetime", "landing_clicked_period"];
        foreach ($removeColumns as $column) {
            $this->removeColumn($column);
        }
        $this->addColumn(new \Component\Grid\Definition\Column("profitability", ["type" => \Component\Grid\Definition\Column::INTEGER, "th_title" => "#", "sortable" => true, "groupable" => false, "category" => "money", "sort_by" => "profit", "metric" => true, "inner_select" => ["revenue - cost" => "profitability"], "formatter" => \Component\Grid\Definition\Column::FORMATTER_PROFITABILITY, "filter" => ["type" => \Component\Grid\Definition\Column::BOOLEAN], "resizable" => false, "exclude_from_details" => true]));
        $this->addColumn(new \Component\Grid\Definition\Column("conversion_id", ["type" => \Component\Grid\Definition\Column::INTEGER, "title" => "conversions.conversion_id", "sortable" => true, "groupable" => true, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("click_datetime", ["type" => \Component\Grid\Definition\Column::DATETIME, "sortable" => true, "formatter" => \Component\Grid\Definition\Column::FORMATTER_DATETIME, "required_columns" => ["conversion_id"], "clickable" => true, "category" => "data", "width" => 160]));
        $this->addColumn(new \Component\Grid\Definition\Column("postback_datetime", ["type" => \Component\Grid\Definition\Column::DATETIME, "sortable" => true, "formatter" => \Component\Grid\Definition\Column::FORMATTER_DATETIME, "required_columns" => ["conversion_id"], "clickable" => true, "category" => "data", "width" => 160]));
        $this->addColumn(new \Component\Grid\Definition\Column("sale_datetime", ["type" => \Component\Grid\Definition\Column::DATETIME, "sortable" => true, "formatter" => \Component\Grid\Definition\Column::FORMATTER_DATETIME, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("sale_period", ["type" => \Component\Grid\Definition\Column::STRING, "th_title" => "grid.sale_period_th", "metric" => true, "inner_select" => ["UNIX_TIMESTAMP(sale_datetime) - UNIX_TIMESTAMP(click_datetime)" => "sale_period"], "sortable" => true, "category" => "data", "filter" => ["type" => \Component\Grid\Definition\Column::INTEGER], "formatter" => \Component\Grid\Definition\Column::FORMATTER_TIME_DIFF]));
        $this->addColumn(new \Component\Grid\Definition\Column("tid", ["type" => \Component\Grid\Definition\Column::STRING, "title" => "conversions.tid", "filter" => ["type" => \Component\Grid\Definition\Column::STRING], "sortable" => true, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("revenue", ["type" => \Component\Grid\Definition\Column::DECIMAL, "metric" => true, "sortable" => true, "summary" => true, "category" => "money", "filter" => ["type" => \Component\Grid\Definition\Column::DECIMAL], "inner_select" => ["SUM(`revenue`)" => "revenue"], "formatter" => \Component\Grid\Definition\Column::FORMATTER_MONEY_H, "fraction_size" => 2]));
        $this->addColumn(new \Component\Grid\Definition\Column("profit", ["type" => \Component\Grid\Definition\Column::DECIMAL, "metric" => true, "sortable" => true, "filter" => ["type" => \Component\Grid\Definition\Column::DECIMAL], "category" => "money", "formatter" => \Component\Grid\Definition\Column::FORMATTER_MONEY_H, "inner_select" => ["SUM(`revenue`) - SUM(`cost`)" => "profit"], "summary" => true, "fraction_size" => 2]));
        $this->addColumn(new \Component\Grid\Definition\Column("status", ["type" => \Component\Grid\Definition\Column::STRING, "filter" => ["type" => \Component\Grid\Definition\Column::ENUM, "dictionary" => ["valueProp" => "id", "url" => "?object=conversions.statuses", "group" => true]], "sortable" => true, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("previous_status", ["type" => \Component\Grid\Definition\Column::STRING, "title" => "conversions.previous_status", "filter" => ["type" => \Component\Grid\Definition\Column::ENUM, "dictionary" => ["valueProp" => "id", "url" => "?object=conversions.statuses", "group" => true]], "sortable" => true, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("original_status", ["type" => \Component\Grid\Definition\Column::STRING, "title" => "conversions.original_status", "th_title" => "conversions.original_status_th", "filter" => ["type" => \Component\Grid\Definition\Column::STRING], "sortable" => true, "category" => "data"]));
        $this->addColumn(new \Component\Grid\Definition\Column("params", ["type" => \Component\Grid\Definition\Column::STRING, "category" => "data", "decorator" => \Component\Grid\Builder\Decorator::PARSE_JSON, "formatter" => \Component\Grid\Definition\Column::FORMATTER_OBJECT]));
    }
}

?>