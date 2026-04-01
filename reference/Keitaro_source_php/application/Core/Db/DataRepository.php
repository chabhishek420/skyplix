<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Db;

class DataRepository extends \Traffic\Repository\AbstractBaseRepository
{
    public function all(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $select = NULL, $groupBy = NULL, $joins = NULL)
    {
        if (empty($select)) {
            $select = "*";
        }
        $rows = $this->rawRows($definition, $select, $where, $order, $limit, $offset, $groupBy, $joins);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->restoreEntity($definition, $row);
        }
        return $items;
    }
    public function existsById(\Core\Entity\Definition\EntityDefinition $definition, $id)
    {
        return $this->exists($definition, "id = " . Db::quote($id));
    }
    public function exists(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL)
    {
        $sql = "SELECT COUNT(*) FROM " . $definition->tableName();
        if (isset($where)) {
            $sql .= " WHERE " . $where;
        }
        return 0 < Db::instance()->getOne($sql);
    }
    public function findByIds(\Core\Entity\Definition\EntityDefinition $definition, $modelIds)
    {
        if (empty($modelIds)) {
            $modelIds = [-1];
        }
        return $this->all($definition, "id in (" . implode(",", Db::quote($modelIds)) . ")");
    }
    public function getAllIds(\Core\Entity\Definition\EntityDefinition $definition)
    {
        return $this->pluck($definition, NULL, $definition->primaryKey());
    }
    public function columnExists(\Core\Entity\Definition\EntityDefinition $definition, $column)
    {
        $sql = "SHOW COLUMNS FROM `" . $definition->tableName() . "` LIKE " . Db::quote($column);
        $result = Db::instance()->getOne($sql);
        return !empty($result);
    }
    public function tableExists(\Core\Entity\Definition\EntityDefinition $definition)
    {
        $sql = "SELECT 1 FROM " . $definition->tableName() . " LIMIT 1";
        try {
            Db::instance()->getOne($sql);
            return true;
        } catch (\ADODB_Exception $e) {
            return false;
        }
    }
    public function find(\Core\Entity\Definition\EntityDefinition $definition, $id)
    {
        $data = $this->findRaw($definition, $id);
        if (is_array($data)) {
            return $this->restoreEntity($definition, $data);
        }
        return NULL;
    }
    public function findRaw(\Core\Entity\Definition\EntityDefinition $definition, $id)
    {
        $sql = "SELECT * FROM " . $definition->tableName() . " WHERE `" . $definition->primaryKey() . "` = '" . (int) $id . "'";
        $data = Db::instance()->getRow($sql);
        if (!isset($data[$definition->primaryKey()])) {
            throw new \Core\Exceptions\NotFoundError($definition->className() . " #" . (int) $id . " not found");
        }
        return $data;
    }
    public function findEach(\Core\Entity\Definition\EntityDefinition $definition, $func, $chunkSize, $where = NULL, $order = NULL)
    {
        $total = $this->count($definition, $where);
        $chunks = ceil($total / $chunkSize);
        for ($num = 0; $num < $chunks; $num++) {
            $offset = $num * $chunkSize;
            $items = $this->all($definition, $where, $order, $chunkSize, $offset);
            foreach ($items as $item) {
                call_user_func($func, $item);
            }
        }
    }
    public function rawRows(\Core\Entity\Definition\EntityDefinition $definition, $select = NULL, $where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $groupBy = NULL, $joins = NULL)
    {
        if (empty($select)) {
            $select = "*";
        }
        $sql = "SELECT " . $select . " FROM " . $definition->tableName() . " as t";
        if (isset($joins)) {
            if (!is_array($joins)) {
                throw new \Exception("joins must be an array");
            }
            foreach ($joins as $join) {
                $sql .= " " . $join . " ";
            }
        }
        if (isset($where)) {
            $sql .= " WHERE " . $where;
        }
        if (isset($groupBy)) {
            $sql .= " GROUP BY " . $groupBy;
        }
        if (isset($order)) {
            $sql .= " ORDER BY " . $order;
        }
        if (isset($limit)) {
            $sql .= " LIMIT  ";
            if (isset($offset)) {
                $sql .= (int) $offset . ", ";
            }
            $sql .= (int) $limit;
        }
        return Db::instance()->getAll($sql);
    }
    public function pluck(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL, $select = NULL, $order = NULL)
    {
        $rows = $this->rawRows($definition, $select, $where, $order);
        $items = [];
        foreach ($rows as $row) {
            $items[] = $row[$select];
        }
        return $items;
    }
    public function findFirst(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL, $order = NULL)
    {
        $row = $this->fetchRow($definition, "*", $where, $order);
        if (!empty($row)) {
            return $this->restoreEntity($definition, $row);
        }
        return NULL;
    }
    public function fetchRow(\Core\Entity\Definition\EntityDefinition $definition, $select = "*", $where = NULL, $order = NULL, $group = NULL)
    {
        $sql = $this->_prepareSqlForOne($definition, $select, $where, $order, $group);
        return Db::instance()->fetchRow($sql);
    }
    public function getOne(\Core\Entity\Definition\EntityDefinition $definition, $select, $where = NULL, $order = NULL)
    {
        $sql = $this->_prepareSqlForOne($definition, $select, $where, $order);
        return Db::instance()->getOne($sql);
    }
    public function findLast(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL)
    {
        $sql = "SELECT * FROM " . $definition->tableName();
        if (isset($where)) {
            $sql .= " WHERE " . $where;
        }
        $sql .= " ORDER BY " . $definition->primaryKey() . " DESC";
        $sql .= " LIMIT 1";
        $row = Db::instance()->getRow($sql);
        if ($row) {
            return $this->restoreEntity($definition, $row);
        }
        return NULL;
    }
    public function restoreEntity(\Core\Entity\Definition\EntityDefinition $definition, $rawData)
    {
        $className = $definition->className();
        $data = \Core\Entity\Service\DataConverterService::instance()->restoreFromMysql($definition->fields(), $rawData);
        return new $className($data);
    }
    public function count(\Core\Entity\Definition\EntityDefinition $definition, $where = NULL)
    {
        $sql = "SELECT COUNT(*) FROM " . $definition->tableName();
        if (isset($where)) {
            $sql .= " WHERE " . $where;
        }
        return Db::instance()->getOne($sql);
    }
    private function _prepareSqlForOne(\Core\Entity\Definition\EntityDefinition $definition, $select, $where, $order, $group = NULL)
    {
        $sql = "SELECT " . $select . "  FROM " . $definition->tableName();
        if (isset($where)) {
            $sql .= " WHERE " . $where;
        }
        if (isset($group)) {
            $sql .= " GROUP BY " . $group;
        }
        if (isset($order)) {
            $sql .= " ORDER BY " . $order;
        }
        $sql .= " LIMIT 1";
        return $sql;
    }
}

?>