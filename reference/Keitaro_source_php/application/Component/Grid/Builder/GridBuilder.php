<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Builder;

class GridBuilder implements BuilderInterface
{
    protected $_definition = NULL;
    protected $_columns = NULL;
    protected $_select = NULL;
    protected $_from = NULL;
    protected $_filter = NULL;
    protected $_sort = NULL;
    protected $_grouping = NULL;
    protected $_limit = NULL;
    protected $_offset = NULL;
    protected $_joins = NULL;
    protected $_queryParams = NULL;
    protected $_range = NULL;
    protected $_restrictedColumns = NULL;
    public static function factory(\Component\Grid\QueryParams\QueryParams $queryParams, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $gridBuilder = new GridBuilder($queryParams, $userParams->getRestrictedColumns());
        $restriction = new \Component\Clicks\Grid\AccessRestriction($userParams->getAllowedCampaignIds());
        $gridBuilder->getFilter()->add($restriction->getFilter());
        $restriction = new \Component\Clicks\Grid\DeletedCampaignRestriction();
        $gridBuilder->getFilter()->add($restriction->getFilter());
        $gridBuilder->setTimezone($userParams->getTimezone());
        return $gridBuilder;
    }
    public function __construct(\Component\Grid\QueryParams\QueryParams $queryParams, $restrictedColumns)
    {
        $this->_restrictedColumns = $restrictedColumns;
        $this->_queryParams = $queryParams;
        $this->_definition = $queryParams->getDefinition();
        $this->_columns = $queryParams->getColumns();
        $this->_select = new \Component\Grid\Query\Select($queryParams->getRequiredColumns(), $this->_definition);
        $this->_from = new \Component\Grid\Query\From($this->_definition);
        $this->_filter = new \Component\Grid\Query\Filter($queryParams->getFilters(), $this->_definition);
        $this->_grouping = new \Component\Grid\Query\Grouping($queryParams->getGrouping(), $this->_definition);
        $this->_sort = new \Component\Grid\Query\Sort($queryParams->getSort(), $this->_definition);
        $this->_limit = new \Component\Grid\Query\Limit($queryParams->getLimit());
        $this->_offset = new \Component\Grid\Query\Offset($queryParams->getOffset());
        $this->_joins = new \Component\Grid\Query\Joins($queryParams->getRequiredColumns(), $this->_definition);
        if ($queryParams->getRange()) {
            $this->_range($queryParams);
        }
    }
    public function getFilter()
    {
        return $this->_filter;
    }
    public function getGrouping()
    {
        return $this->_grouping;
    }
    private function _range(\Component\Grid\QueryParams\QueryParams $params)
    {
        $this->_range = new \Component\Grid\Definition\TimeRange($params->getRange(), $this->_definition->getRangeTimeField());
        $this->_filter->add($this->_range->toFilter());
        return $this;
    }
    public function setTimezone($timezone)
    {
        if ($timezone) {
            $offset = \Component\Users\Repository\TimezoneRepository::instance()->getOffset($timezone);
            \Core\Db\Db::slaveInstance()->execute("SET @timezone = " . \Core\Db\Db::quote($offset));
        }
        return $this;
    }
    public function getRange()
    {
        return $this->_range;
    }
    public function getColumns()
    {
        return $this->_columns;
    }
    public function getDefinition()
    {
        return $this->_definition;
    }
    protected function _getAllowedColumns()
    {
        if (!$this->_restrictedColumns || $this->_restrictedColumns == \Component\Users\Service\AclService::ALLOW_ANY) {
            return $this->_queryParams->getRequiredColumns();
        }
        return array_diff($this->_queryParams->getRequiredColumns(), $this->_restrictedColumns);
    }
    public function getDecorator()
    {
        return new Decorator($this->_getAllowedColumns(), $this->_definition);
    }
    public function getSummary()
    {
        $summary = new \Component\Clicks\Grid\Summary($this->getQuery(), $this->_definition);
        return $summary->get();
    }
    public function build()
    {
        $renderer = \Component\Grid\Renderer\RendererFactory::get($this->_queryParams->getFormat(), $this);
        return $renderer->create($this->_queryParams->summary());
    }
    public function getQuery()
    {
        return new \Component\Grid\Query\Query(["select" => $this->_select, "from" => $this->_from, "joins" => $this->_joins, "filters" => $this->_filter, "sort" => $this->_sort, "grouping" => $this->_grouping, "limit" => $this->_limit, "offset" => $this->_offset]);
    }
    public function fetchRows()
    {
        $postProcessor = new GridPostProcessor($this->getQuery(), $this->getDefinition());
        $rows = $this->getQuery()->fetchRows();
        return $postProcessor->process($rows);
    }
    public function eachChunk($chunkSize, $callback)
    {
        $query = $this->getQuery();
        $query->getLimit()->setValue($chunkSize);
        $postProcessor = new GridPostProcessor($query, $this->getDefinition());
        while (true) {
            $rows = $query->fetchRows();
            if (!empty($rows)) {
                $rows = $postProcessor->process($rows);
                call_user_func($callback, $rows);
                $query->getOffset()->setValue($query->getOffset()->getValue() + $chunkSize);
            }
        }
    }
}

?>