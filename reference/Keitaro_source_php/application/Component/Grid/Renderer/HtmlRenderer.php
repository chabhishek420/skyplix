<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Renderer;

class HtmlRenderer implements RendererInterface
{
    private $_saver = NULL;
    private $_builder = NULL;
    const FORMAT = "html";
    const DIR = "/exports";
    const CSS_FILE = "/application/Component/Grid/data/report_style.css";
    const CHUNK_SIZE = 2000;
    public function __construct(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $this->_saver = new \Component\Grid\ReportSaver(FORMAT);
        $this->_builder = $builder;
    }
    public function create($summary)
    {
        $this->buildHeader($this->_saver);
        $this->buildInformation($this->_saver);
        $this->buildStartTable($this->_saver);
        $this->buildTableHeader($this->_saver);
        $this->buildBody($this->_saver);
        if ($summary) {
            $this->buildSummary($this->_saver);
        }
        $this->buildEndTable($this->_saver);
        $this->buildFooter($this->_saver);
        return ["path" => $this->_saver->getFilePath(), "url" => $this->_saver->getUrl()];
    }
    public function buildHeader(\Component\Grid\ReportSaver $saver)
    {
        if (!file_exists(ROOT . CSS_FILE)) {
            throw new \Component\Grid\Error("File not found " . ROOT . CSS_FILE);
        }
        $html = "<html>";
        $html .= "<head>" . PHP_EOL;
        $html .= "<meta http-equiv=\"content-type\" content=\"text/html; charset=UTF-8\" />" . PHP_EOL;
        $html .= "<style type=\"text/css\">";
        $html .= file_get_contents(ROOT . CSS_FILE);
        $html .= "</style>" . PHP_EOL;
        $html .= "</head>" . PHP_EOL;
        $html .= "<body>";
        $saver->append($html);
    }
    public function buildInformation(\Component\Grid\ReportSaver $saver)
    {
        $html = "<h1>";
        $html .= $this->_getTitle();
        $html .= "</h1>";
        $saver->append($html);
        $html = "<p>";
        $html .= $this->_getRange();
        $html .= "</p>";
        $saver->append($html);
    }
    private function _getTitle()
    {
        return $this->_builder->getDefinition()->getTitle();
    }
    private function _getRange()
    {
        $range = $this->_builder->getRange();
        $from = $range->getStartTime();
        $to = $range->getEndTime();
        $dateFormat = \Core\Locale\LocaleService::t("format.date");
        $datetimeFormat = \Core\Locale\LocaleService::t("format.datetime");
        if (empty($from)) {
            throw new \Component\Grid\Error("Empty 'from' field");
        }
        if (empty($to)) {
            throw new \Component\Grid\Error("Empty 'to' field");
        }
        if ($from->format("Y-m-d") == $to->format("Y-m-d")) {
            return $from->format($dateFormat);
        }
        return $from->format($datetimeFormat) . " — " . $to->format($datetimeFormat);
    }
    private function _getFiltersRow(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $html = "";
        $filters = $builder->getFilters();
        foreach (["range", "campaignId", "cellType"] as $filter) {
            unset($filters[$filter]);
        }
        if (count($filters)) {
            $html = "<p>";
            $html .= \Core\Locale\LocaleService::t("grid.filters") . ": <code>" . json_encode($filters, true) . "</code>";
            $html .= "</p>";
        }
        return $html;
    }
    private function _formatRange($range)
    {
        $result = [];
        if (!empty($range[0])) {
            $result[] = $range[0]->format(\Core\Locale\LocaleService::t("format.date"));
        }
        if (!empty($range[1])) {
            $result[] = $range[1]->format(\Core\Locale\LocaleService::t("format.date"));
        }
        return implode(" — ", $result);
    }
    public function buildStartTable(\Component\Grid\ReportSaver $saver)
    {
        $saver->append("<table>");
    }
    public function buildTableHeader(\Component\Grid\ReportSaver $saver)
    {
        $item = [];
        foreach ($this->_builder->getColumns() as $column) {
            $result = $this->_formatTh($column);
            $item[] = "<th class=\"col-" . $column . $result["class"] . "\">" . $result["title"] . "</th>";
        }
        $html = "<thead>";
        $html .= "<tr>";
        $html .= implode("", $item);
        $html .= "</tr></thead>";
        $saver->append($html);
    }
    public function buildBody(\Component\Grid\ReportSaver $saver)
    {
        $context = $this;
        $saver->append("<tbody>");
        $this->_builder->eachChunk(CHUNK_SIZE, function ($rows) {
            $context->_appendRows($rows, $saver);
        });
        $saver->append("</tbody>");
    }
    private function _appendRows($rows, \Component\Grid\ReportSaver $saver)
    {
        $html = "";
        $rows = $this->_builder->getDecorator()->decorateRows($rows);
        foreach ($rows as $key => $row) {
            $html .= $this->_buildRow($row);
        }
        $saver->append($html);
    }
    private function _buildRow($row)
    {
        $item = [];
        foreach ($this->_builder->getColumns() as $columnName) {
            $value = isset($row[$columnName]) ? $row[$columnName] : NULL;
            $result = $this->_formatTd($columnName, $value);
            $item[] = "<td class=\"" . trim($result["class"]) . "\">" . $result["value"] . "</td>";
        }
        return "<tr>" . implode("", $item) . "</tr>";
    }
    public function buildSummary(\Component\Grid\ReportSaver $saver)
    {
        $summary = $this->_builder->getSummary();
        $item = [];
        foreach ($this->_builder->getColumns() as $column) {
            $item[] = $this->_buildSummaryCell($summary, $column);
        }
        $html = "<tfoot><tr>" . implode("", $item) . "</tr></tfoot>";
        $saver->append($html);
    }
    private function _buildSummaryCell($summary, $columnName)
    {
        $value = isset($summary[$columnName]) ? $summary[$columnName] : NULL;
        $result = $this->_formatTd($columnName, $value);
        return "<td class=\"" . trim($result["class"]) . "\">" . $result["value"] . "</td>";
    }
    public function buildEndTable(\Component\Grid\ReportSaver $saver)
    {
        $saver->append("<table>");
    }
    public function buildFooter(\Component\Grid\ReportSaver $saver)
    {
        $tz = \Component\Users\Service\CurrentUserService::instance()->getTimezone();
        $datetime = new \DateTime();
        $datetime->setTimezone($tz);
        $html = "";
        $html .= "<p class=\"smaller text-right\">" . \Core\Locale\LocaleService::t("grid.report_datetime") . " ";
        $html .= $datetime->format(\Core\Locale\LocaleService::t("format.datetime"));
        $html .= "</p>";
        $saver->append($html);
        $html = "</body>";
        $html .= "</html>";
        $saver->append($html);
    }
    private function _formatTh($columnName)
    {
        $column = $this->_builder->getDefinition()->getColumn($columnName);
        $title = \Core\Locale\LocaleService::t($column->getThTitle());
        $class = $this->_getCellClass($column->getType());
        return ["title" => $title, "class" => $class];
    }
    private function _formatTd($columnName, $value)
    {
        $column = $this->_builder->getDefinition()->getColumn($columnName);
        $tz = \Component\Users\Service\CurrentUserService::instance()->getTimezone();
        $decimalSep = \Core\Locale\LocaleService::t("format.decimal");
        $thousandSep = \Core\Locale\LocaleService::t("format.thousand");
        $moneyDecimals = 4;
        $currency = \Core\Currency\Service\CurrencyService::instance()->getCurrentSymbol();
        $column->getFormatter();
        switch ($column->getFormatter()) {
            case \Component\Grid\Definition\Column::FORMATTER_DATETIME:
                $datetime = new \DateTime($value);
                $datetime->setTimezone($tz);
                $value = $datetime->format(\Core\Locale\LocaleService::t("format.datetime"));
                break;
            case \Component\Grid\Definition\Column::FORMATTER_HOUR:
                $value = str_pad($value, 2, "0", STR_PAD_LEFT) . ":00 — " . str_pad($value + 1, 2, "0", STR_PAD_LEFT) . ":00";
                break;
            case \Component\Grid\Definition\Column::FORMATTER_MONTH:
                $month = substr($value, 5, 7);
                $year = substr($value, 0, 4);
                $value = \Core\Locale\LocaleService::t("calendar.months." . ($month - 1)) . " " . $year;
                break;
            case \Component\Grid\Definition\Column::FORMATTER_WEEK:
                $value = \Core\Locale\LocaleService::t("calendar.week_days." . $value);
                break;
            case \Component\Grid\Definition\Column::FORMATTER_MONEY:
            case \Component\Grid\Definition\Column::FORMATTER_MONEY_H:
            case \Component\Grid\Definition\Column::FORMATTER_MONEY_H_NEGATIVE:
                $value = number_format($value, $moneyDecimals, $decimalSep, $thousandSep) . " " . $currency;
                break;
            case \Component\Grid\Definition\Column::FORMATTER_PERCENTAGE:
            case \Component\Grid\Definition\Column::FORMATTER_PERCENTAGE_H:
            case \Component\Grid\Definition\Column::FORMATTER_PERCENTAGE_H_NEGATIVE:
                $value = number_format($value, 2, $decimalSep, $thousandSep) . " %";
                break;
            case \Component\Grid\Definition\Column::FORMATTER_OBJECT:
                $value = json_encode((int) $value);
                break;
            default:
                $class = $this->_getCellClass($column->getType());
                return ["value" => $value, "class" => $class];
        }
    }
    private function _getCellClass($type)
    {
        switch ($type) {
            case \Component\Grid\Definition\Column::INTEGER:
            case \Component\Grid\Definition\Column::DECIMAL:
                return " text-right";
                break;
            default:
                return "";
        }
    }
}

?>