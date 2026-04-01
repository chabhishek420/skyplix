<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Grid;

class CampaignGridDefinition extends \Component\Reports\Grid\ReportDefinition
{
    protected $_url = "?object=campaigns.withStats";
    public function getTitle()
    {
        return \Core\Locale\LocaleService::t("campaigns.title");
    }
    public function initColumns()
    {
        self::initColumns();
        $this->addColumn(new \Component\Grid\Definition\Column("checkbox", ["type" => \Component\Grid\Definition\Column::BOOLEAN, "th_title" => "#", "virtual" => true, "template" => "CheckboxCell", "category" => "data", "width" => 28, "resizable" => false]));
        $this->addColumn(new \Component\Grid\Definition\Column("state", ["type" => \Component\Grid\Definition\Column::STRING, "th_title" => "#", "sortable" => true, "virtual" => true, "template" => "CampaignStateCell", "category" => "data", "width" => 20, "resizable" => false]));
        $this->addColumn(new \Component\Grid\Definition\Column("id", ["type" => \Component\Grid\Definition\Column::INTEGER, "sortable" => true, "virtual" => true, "category" => "data", "width" => 41]));
        $this->addColumn(new \Component\Grid\Definition\Column("name", ["type" => \Component\Grid\Definition\Column::STRING, "sortable" => true, "virtual" => true, "template" => "CampaignLinkCell", "category" => "data", "width" => 200]));
        $this->addColumn(new \Component\Grid\Definition\Column("group", ["type" => \Component\Grid\Definition\Column::STRING, "sortable" => true, "virtual" => true, "category" => "data", "width" => 100]));
        $this->addColumn(new \Component\Grid\Definition\Column("ts", ["type" => \Component\Grid\Definition\Column::STRING, "metric" => true, "sortable" => true, "virtual" => true, "category" => "data", "width" => 100]));
        $this->addColumn(new \Component\Grid\Definition\Column("streams_count", ["type" => \Component\Grid\Definition\Column::INTEGER, "metric" => true, "sortable" => true, "virtual" => true, "summary" => true, "template" => "CampaignLinkCell", "category" => "data", "formatter" => \Component\Grid\Definition\Column::FORMATTER_INTEGER, "width" => 65]));
        $this->addColumn(new \Component\Grid\Definition\Column("more", ["type" => \Component\Grid\Definition\Column::BOOLEAN, "metric" => true, "sortable" => true, "virtual" => true, "template" => "CampaignMoreCell", "category" => "data", "th_title" => "#", "width" => 90]));
        $this->getColumn("campaign_id")->set(["groupable" => true]);
    }
}

?>