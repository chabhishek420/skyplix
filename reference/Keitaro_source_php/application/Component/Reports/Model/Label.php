<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Model;

class Label extends \Core\Model\AbstractModel
{
    protected static $_fields = NULL;
    protected static $_tableName = "labels";
    public static function validator()
    {
        return new \Component\Reports\Validator\LabelValidator();
    }
    public static function service()
    {
        return \Component\Reports\Service\LabelService::instance();
    }
    public function getLabelName()
    {
        return $this->get("label_name");
    }
    public function getRefName()
    {
        return $this->get("ref_name");
    }
    public function getRefId()
    {
        return $this->get("ref_id");
    }
}

?>