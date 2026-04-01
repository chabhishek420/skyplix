<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Model;

class AclReport extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "acl_reports";
    public static function validator()
    {
        return new \Component\Users\Validator\AclReportsValidator();
    }
    public static function repository()
    {
        return \Component\Users\Repository\AclReportRepository::instance();
    }
    public static function service()
    {
        return \Component\Users\Service\AclReportService::instance();
    }
    public function getColumns()
    {
        return $this->get("columns");
    }
}

?>