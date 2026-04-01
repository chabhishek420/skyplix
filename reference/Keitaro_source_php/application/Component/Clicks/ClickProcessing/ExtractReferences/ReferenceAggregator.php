<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing\ExtractReferences;

class ReferenceAggregator
{
    private $_storage = [];
    private $_relation = NULL;
    const MAX_LENGTH = 255;
    public function __construct(\Component\Grid\Definition\Relation $relation)
    {
        if (empty($relation)) {
            throw new \Core\Application\Exception\Error("Relation is not provided");
        }
        $this->_relation = $relation;
    }
    public function add($value)
    {
        $value = $this->_sanitize($value);
        $refId = \Component\Clicks\Memory::instance()->get($this->_relation->getName(), $value);
        $this->_storage[$value] = $refId;
    }
    public function loadIds($values = NULL)
    {
        if (empty($values)) {
            $values = $this->getAllValues();
        }
        $values = array_map(function ($item) {
            return (int) $item;
        }, $values);
        if (!empty($values)) {
            $where[] = "`value` IN (" . implode(",", \Core\Db\Db::quote($values)) . ")";
            $class = $this->_relation->getClass();
            $rows = $class::fetchRows("*", implode(" AND ", $where), "id");
            foreach ($rows as $row) {
                $this->setId($row["value"], $row["id"]);
            }
        }
    }
    public function setId($value, $id)
    {
        $value = $this->_sanitize($value);
        \Component\Clicks\Memory::instance()->set($this->_relation->getName(), $value, $id);
        $this->_storage[$value] = $id;
    }
    public function getAllValues()
    {
        return array_keys($this->_storage);
    }
    public function getStorage()
    {
        return $this->_storage;
    }
    public function getId($value)
    {
        $value = $this->_sanitize($value);
        if (!key_exists($value, $this->_storage)) {
            throw new \Core\Exception("ID not found for value '" . $value . "', relation '" . $this->_relation->getName() . "'");
        }
        return $this->_storage[$value];
    }
    public function getNewValues()
    {
        $items = array_filter($this->_storage, function ($value) {
            return empty($value);
        });
        return array_keys($items);
    }
    public function commit()
    {
        $items = [];
        $valuesToCreate = $this->getNewValues();
        foreach ($valuesToCreate as $value) {
            $data = ["value" => $value];
            $items[] = $data;
        }
        \Core\Db\Db::instance()->multiInsert($this->_relation->getTableName(), $items, true);
        $this->loadIds($valuesToCreate);
    }
    private function _sanitize($value)
    {
        $value = (int) $value;
        if (0 < strlen($value)) {
            $value = mb_substr($value, 0, MAX_LENGTH, "utf-8");
        }
        return $value;
    }
}

?>