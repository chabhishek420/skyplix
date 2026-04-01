<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class Landing extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    use Mixin\StateMethodsTrait;
    use Mixin\StreamActionableMethodsTrait;
    protected static $_fields = NULL;
    protected static $_className = "Landing";
    protected static $_tableName = "landings";
    protected static $_cacheKey = "LANDING";
    protected static $_aclKey = "landings";
    protected static $_entityName = "landing";
    public static function serializer()
    {
        return new \Component\Landings\Serializer\LandingSerializer(true);
    }
    public static function validator()
    {
        return new \Component\Landings\Validator\LandingValidator();
    }
    public static function reportDefinition()
    {
        return new \Component\Landings\Grid\LandingGridDefinition();
    }
    public static function service()
    {
        return \Component\Landings\Service\LandingService::instance();
    }
    public static function repository()
    {
        return \Component\Landings\Repository\LandingRepository::instance();
    }
}

?>