<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Renderer;

class CsvRenderer implements RendererInterface
{
    private $_saver = NULL;
    private $_builder = NULL;
    const FORMAT = "csv";
    const SEPARATOR = ";";
    const CHUNK_SIZE = 10000;
    public function __construct(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $this->_saver = new \Component\Grid\ReportSaver(FORMAT);
        $this->_builder = $builder;
    }
    public function create($summary)
    {
        $this->_buildHeader($this->_builder);
        $this->_buildBody($this->_builder, $this->_saver);
        if ($summary) {
            $this->_buildSummary($this->_builder);
        }
        return ["path" => $this->_saver->getFilePath(), "url" => $this->_saver->getUrl()];
    }
    private function _buildHeader(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $header = [];
        foreach ($builder->getColumns() as $columnName) {
            $column = $this->_builder->getDefinition()->getColumn($columnName);
            $name = \Core\Locale\LocaleService::t($column->getThTitle());
            $header[] = $name;
        }
        $this->_saver->append(implode(SEPARATOR, $header));
    }
    private function _buildBody(\Component\Grid\Builder\BuilderInterface $builder, \Component\Grid\ReportSaver $saver)
    {
        $context = $this;
        $builder->eachChunk(CHUNK_SIZE, function ($rows) {
            $context->append($builder, $saver, $rows);
        });
    }
    private function append(\Component\Grid\Builder\BuilderInterface $builder, \Component\Grid\ReportSaver $saver, $rows)
    {
        $rows = $builder->getDecorator()->decorateRows($rows);
        foreach ($rows as $key => $row) {
            $subItem = [];
            foreach ($builder->getColumns() as $column) {
                $subItem[] = $this->_getColumnValue($builder, $row, $column);
            }
            $saver->append($this->formatRow($subItem));
        }
    }
    private function _getColumnValue(\Component\Grid\Builder\BuilderInterface $builder, $row, $columnName)
    {
        $value = isset($row[$columnName]) ? $row[$columnName] : NULL;
        if (!empty($value)) {
            $info = $builder->getDefinition()->getColumn($columnName);
            if ($info->getType() == \Component\Grid\Definition\Column::DATETIME) {
                $date = new \DateTime($value);
                $date->setTimezone($builder->getRange()->getTimezone());
                $value = $date->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
            }
            if ($info->getFormatter() == \Component\Grid\Definition\Column::FORMATTER_OBJECT) {
                $value = json_encode((int) $value);
            }
        }
        return $value;
    }
    private function formatRow($row)
    {
        ob_start();
        $outputHandler = fopen("php://output", "w");
        fputcsv($outputHandler, $row, SEPARATOR);
        fclose($outputHandler);
        return rtrim(ob_get_clean());
    }
    private function _buildSummary(\Component\Grid\Builder\BuilderInterface $builder)
    {
        $columns = $builder->getColumns();
        $summary = $builder->getSummary();
        if ($summary) {
            $subItem = [];
            foreach ($columns as $column) {
                $subItem[] = $summary[$column];
            }
            $this->_saver->append(implode(SEPARATOR, $subItem));
        }
    }
}

?>