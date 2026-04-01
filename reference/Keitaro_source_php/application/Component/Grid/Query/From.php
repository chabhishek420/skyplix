<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Query;

class From
{
    private $_reportInfo = NULL;
    public function __construct(\Component\Grid\Definition\GridDefinition $info)
    {
        $this->_reportInfo = $info;
    }
    public function getSql($hasDatetimeFilter = false)
    {
        $fullTable = $this->_reportInfo->getFullTableName();
        $table = $this->_reportInfo->getTable();
        $result = "FROM " . $fullTable . " AS t_" . $table;
        if ($hasDatetimeFilter) {
            $result .= " FORCE INDEX(datetime)";
        }
        return $result;
    }
}

?>