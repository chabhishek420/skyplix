<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing\ExtractVisitors;

class VisitorAggregator
{
    private $_storage = [];
    private $_attributes = NULL;
    const MEMO_NAME = "visitors";
    public function __construct()
    {
        $this->_attributes = array_keys(\Component\Clicks\Model\Visitor::getFields());
    }
    public function add($rawClick)
    {
        $code = (int) $rawClick["visitor_code"];
        $id = \Component\Clicks\Memory::instance()->get(MEMO_NAME, $code);
        $item = [];
        if (!empty($id)) {
            $this->_storage[(int) $code] = ["id" => $id];
        } else {
            foreach ($this->_attributes as $attr) {
                if (array_key_exists($attr, $rawClick)) {
                    $item[$attr] = $rawClick[$attr];
                }
            }
            $this->_storage[$code] = $item;
        }
    }
    public function loadIds($values = NULL)
    {
        if (empty($values)) {
            $values = $this->getAllCodes();
        }
        $values = array_map(function ($item) {
            return (int) $item;
        }, $values);
        if (!empty($values)) {
            $where = "visitor_code IN (" . implode(",", \Core\Db\Db::quote($values)) . ")";
            foreach (\Component\Clicks\Model\Visitor::fetchRows("id, visitor_code", $where) as $row) {
                $this->setId($row["visitor_code"], $row["id"]);
            }
        }
    }
    public function setId($code, $id)
    {
        $code = (int) $code;
        \Component\Clicks\Memory::instance()->set(MEMO_NAME, $code, $id);
        $this->_storage[$code]["id"] = $id;
    }
    public function getAllCodes()
    {
        return array_keys($this->_storage);
    }
    public function getId($code)
    {
        $code = (int) $code;
        if (!key_exists((int) $code, $this->_storage)) {
            throw new \Core\Exception("Visitor id not found for code '" . $code . "'");
        }
        return $this->_storage[$code];
    }
    public function getNewVisitors()
    {
        return array_filter($this->_storage, function ($item) {
            return empty($item["id"]);
        });
    }
    public function commit()
    {
        $visitorsToCreate = $this->getNewVisitors();
        \Core\Db\Db::instance()->startFastInsert();
        \Core\Db\Db::instance()->multiInsert(\Component\Clicks\Model\Visitor::getTableName(), $visitorsToCreate);
        \Core\Db\Db::instance()->endFastInsert();
        $this->loadIds(array_keys($visitorsToCreate));
    }
}

?>