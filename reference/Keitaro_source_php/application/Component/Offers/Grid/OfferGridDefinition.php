<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Grid;

class OfferGridDefinition extends \Component\Reports\Grid\ReportDefinition
{
    protected $_url = "?object=offers.withStats";
    public function getTitle()
    {
        return \Core\Locale\LocaleService::t("offers.title");
    }
    public function initColumns()
    {
        self::initColumns();
        $this->removeColumn("lp_clicks");
        $this->removeColumn("lp_ctr");
        $this->addColumn(new \Component\Grid\Definition\Column("checkbox", ["type" => \Component\Grid\Definition\Column::INTEGER, "th_title" => "#", "virtual" => true, "template" => "CheckboxCell", "category" => "data", "width" => 28, "resizable" => false]));
        $this->addColumn(new \Component\Grid\Definition\Column("id", ["type" => \Component\Grid\Definition\Column::INTEGER, "sortable" => true, "virtual" => true, "category" => "data", "width" => 50]));
        $this->addColumn(new \Component\Grid\Definition\Column("name", ["type" => \Component\Grid\Definition\Column::STRING, "sortable" => true, "virtual" => true, "clickable" => true, "template" => "OfferNameCell", "category" => "data", "width" => 200]));
        $this->addColumn(new \Component\Grid\Definition\Column("country", ["type" => \Component\Grid\Definition\Column::JSON, "metric" => true, "sortable" => true, "virtual" => true, "category" => "data", "width" => 60, "formatter" => \Component\Grid\Definition\Column::FORMATTER_LIST]));
        $this->addColumn(new \Component\Grid\Definition\Column("group", ["type" => \Component\Grid\Definition\Column::STRING, "metric" => true, "sortable" => true, "virtual" => true, "category" => "data", "width" => 100]));
        $this->addColumn(new \Component\Grid\Definition\Column("affiliate_network", ["type" => \Component\Grid\Definition\Column::STRING, "metric" => true, "th_title" => "grid.affiliate_network_th", "sortable" => true, "virtual" => true, "category" => "data", "width" => 100]));
        $this->addColumn(new \Component\Grid\Definition\Column("notes", ["type" => \Component\Grid\Definition\Column::STRING, "th_title" => "#", "sortable" => false, "virtual" => true, "template" => "OfferNotesCell", "category" => "data", "width" => 25, "resizable" => false, "metric" => true]));
        $this->addColumn(new \Component\Grid\Definition\Column("conversion_cap", ["type" => \Component\Grid\Definition\Column::STRING, "sortable" => false, "virtual" => true, "template" => "OfferConversionCapCell", "category" => "data", "width" => 106, "resizable" => true, "metric" => true]));
        $this->getColumn("offer_id")->set(["groupable" => true]);
    }
}

?>