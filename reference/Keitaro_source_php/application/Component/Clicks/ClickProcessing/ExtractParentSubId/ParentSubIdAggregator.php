<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing\ExtractParentSubId;

class ParentSubIdAggregator
{
    private $_storage = [];
    public function add($rawClick)
    {
        $subId = $rawClick["sub_id"];
        $parentSubId = $rawClick["parent_sub_id"];
        $this->_storage[] = ["sub_id" => $subId, "parent_sub_id" => $parentSubId];
    }
    public function commit()
    {
        $insertIgnore = true;
        \Core\Db\Db::instance()->startFastInsert();
        \Core\Db\Db::instance()->multiInsert(\Component\Clicks\Model\ClickLink::getTableName(), $this->_storage, $insertIgnore);
        \Core\Db\Db::instance()->endFastInsert();
    }
}

?>