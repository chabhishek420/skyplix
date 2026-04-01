<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class TrafficSource extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_className = "TrafficSource";
    protected static $_tableName = "traffic_sources";
    protected static $_cacheKey = "SOURCE";
    protected static $_aclKey = "traffic_sources";
    protected static $_entityName = "ts";
    public static function serializer()
    {
        return new \Component\TrafficSources\Serializer\TrafficSourceSerializer();
    }
    public static function repository()
    {
        return \Component\TrafficSources\Repository\TrafficSourceRepository::instance();
    }
    public static function service()
    {
        return \Component\TrafficSources\Service\TrafficSourceService::instance();
    }
    public static function reportDefinition()
    {
        return new \Component\TrafficSources\Grid\TrafficSourceGridDefinition();
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function getPostback()
    {
        return $this->get("postback_url");
    }
    public function getParameters()
    {
        return $this->get("parameters");
    }
    public function getTrafficLoss()
    {
        return $this->get("traffic_loss");
    }
    public function getPostbackStatuses()
    {
        return $this->get("postback_statuses");
    }
    public static function validator()
    {
        return new \Component\TrafficSources\Validator\TrafficSourceValidator();
    }
}

?>