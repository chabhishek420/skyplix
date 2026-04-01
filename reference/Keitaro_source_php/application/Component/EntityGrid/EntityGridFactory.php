<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\EntityGrid;

class EntityGridFactory
{
    private $_userParams = NULL;
    private $_params = NULL;
    private $_entityDefinition = NULL;
    private $_shouldMerge = true;
    const WITH_CLICKS = "with_clicks";
    public function userParams(\Component\Grid\QueryParams\UserParams $userParams)
    {
        $this->_userParams = $userParams;
        return $this;
    }
    public function params($params)
    {
        $this->_params = $params;
        return $this;
    }
    public function setEntityDefinition(\Core\Entity\Definition\EntityDefinition $entityDefinition)
    {
        $this->_entityDefinition = $entityDefinition;
        return $this;
    }
    private function _validate()
    {
        if (empty($this->_entityDefinition)) {
            throw new \Core\Application\Exception\Error("No modelClassName");
        }
        if (empty($this->_userParams)) {
            throw new \Core\Application\Exception\Error("No userParams");
        }
        if (empty($this->_params)) {
            throw new \Core\Application\Exception\Error("No params");
        }
        $this->_params["filters"] = isset($this->_params["filters"]) ? $this->_params["filters"] : [];
    }
    private function _getEntities()
    {
        $where = [];
        if (!empty($this->_params["filters"])) {
            $fields = $this->_entityDefinition->fields();
            foreach ($this->_params["filters"] as $index => $filter) {
                if ($fields[$filter["name"]]) {
                    if ($filter["name"] == "state" && $filter["expression"] == WITH_CLICKS) {
                        $this->_shouldMerge = false;
                    } else {
                        $where[] = $filter["name"] . " = " . \Core\Db\Db::quote($filter["expression"]);
                    }
                    unset($this->_params["filters"][$index]);
                }
            }
        }
        $where[] = "t.state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $repo = $this->_entityDefinition->repository();
        if (method_exists($repo, "allWithRelations")) {
            $items = $repo->allWithRelations(implode(" AND ", $where));
        } else {
            $items = $repo->all(implode(" AND ", $where));
        }
        $items = \Component\Users\Service\AclService::instance()->filterByAcl($items, false, $this->_userParams->getUser());
        return \Core\Json\SerializerFactory::serialize($items, $this->_entityDefinition->serializer());
    }
    public function build()
    {
        $this->_validate();
        $entities = $this->_getEntities();
        $stats = $this->stats($entities);
        return ["rows" => $this->_merge($entities, $stats["rows"]), "meta" => $stats["meta"]];
    }
    public function stats($entities)
    {
        $column = $this->_entityDefinition->entityName();
        $ids = array_pluck($entities, "id");
        $filters = $this->_params["filters"];
        $filters[] = ["name" => $column . "_id", "operator" => \Component\Grid\Query\FilterItem::IN_LIST, "expression" => $ids];
        $metrics = $this->_filteredMetrics();
        $params = ["limit" => isset($this->_params["limit"]) ? $this->_params["limit"] : NULL, "range" => isset($this->_params["range"]) ? $this->_params["range"] : NULL, "grouping" => [$column . "_id"], "metrics" => $metrics, "filters" => $filters];
        if (empty($params["metrics"])) {
            return ["rows" => [], "meta" => ["total" => count($entities)]];
        }
        return \Component\Reports\Repository\ReportRepository::instance()->get($params, $this->_userParams, $this->_entityDefinition->reportDefinition());
    }
    private function _filteredMetrics()
    {
        $exceptions = ["more"];
        if (isset($this->_params["metrics"])) {
            $metrics = $this->_params["metrics"];
            foreach ($exceptions as $check) {
                if (($key = array_search($check, $metrics)) !== false) {
                    unset($metrics[$key]);
                }
            }
            return $metrics;
        } else {
            return NULL;
        }
    }
    private function _merge($rows, $statRows)
    {
        $columns = isset($this->_params["metrics"]) ? $this->_params["metrics"] : [];
        $empty = array_fill_keys($columns, 0);
        $groupedStats = $this->_groupStats($statRows);
        $newRows = [];
        foreach ($rows as $i => $row) {
            if (!empty($groupedStats[$row["id"]])) {
                $newRows[$i] = array_merge($groupedStats[$row["id"]], $row);
            } else {
                if ($this->_shouldMerge) {
                    $newRows[$i] = array_merge($empty, $row);
                }
            }
        }
        return array_values($newRows);
    }
    private function _groupStats($statRows)
    {
        $column = $this->_entityDefinition->entityName() . "_id";
        $result = [];
        foreach ($statRows as $row) {
            $result[$row[$column]] = $row;
        }
        return $result;
    }
}

?>