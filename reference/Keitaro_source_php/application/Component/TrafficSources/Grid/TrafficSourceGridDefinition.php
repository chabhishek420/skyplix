<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Grid;

class TrafficSourceGridDefinition extends \Component\Reports\Grid\ReportDefinition
{
    protected $_url = "?object=trafficSources.withStats";
    public function getTitle()
    {
        return \Core\Locale\LocaleService::t("traffic_sources.title");
    }
    public function initColumns()
    {
        self::initColumns();
        $this->addColumn(new \Component\Grid\Definition\Column("checkbox", ["type" => \Component\Grid\Definition\Column::INTEGER, "th_title" => "#", "virtual" => true, "template" => "CheckboxCell", "category" => "data", "width" => 28, "resizable" => false]));
        $this->addColumn(new \Component\Grid\Definition\Column("id", ["type" => \Component\Grid\Definition\Column::INTEGER, "sortable" => true, "virtual" => true, "category" => "data", "width" => 50]));
        $this->addColumn(new \Component\Grid\Definition\Column("name", ["type" => \Component\Grid\Definition\Column::STRING, "sortable" => true, "virtual" => true, "clickable" => true, "category" => "data", "width" => 200]));
        $this->addColumn(new \Component\Grid\Definition\Column("notes", ["type" => \Component\Grid\Definition\Column::STRING, "th_title" => "#", "sortable" => false, "virtual" => true, "template" => "TrafficSourceNotesCell", "category" => "data", "width" => 25, "resizable" => false, "metric" => true]));
        $this->getColumn("ts_id")->set(["groupable" => true]);
    }
}

?>