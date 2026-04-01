<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Status extends \Traffic\Macros\AbstractConversionMacro
{
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\Model\Conversion $conversion, $mapping = NULL)
    {
        if (!empty($mapping)) {
            $items = explode(" ", $mapping);
            foreach ($items as $item) {
                $param = explode("=", $item);
                if ($param[0] == $conversion->get("status")) {
                    return $param[1];
                }
            }
        } else {
            return $conversion->get("status");
        }
    }
}

?>