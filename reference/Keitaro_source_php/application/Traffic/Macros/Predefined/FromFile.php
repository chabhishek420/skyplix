<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class FromFile extends \Traffic\Macros\AbstractClickMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $visitor, $file)
    {
        $file = \Traffic\Tools\Tools::sanitizeFilename($file);
        return trim(file_get_contents(ROOT . "/var/macros/" . $file));
    }
}

?>