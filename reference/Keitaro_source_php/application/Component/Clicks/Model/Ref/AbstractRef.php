<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Model\Ref;

class AbstractRef extends \Core\Model\AbstractModel
{
    protected static $_fields = NULL;
    public static function getRefName()
    {
        return \Traffic\Tools\Tools::fromCamelCase(\Traffic\Tools\Tools::demodulize(get_called_class()));
    }
}

?>