<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class DeserializeClicks implements StageInterface
{
    public function process($entries)
    {
        foreach ($entries as $i => $entry) {
            $entries[$i]["datetime"] = new \DateTime($entry["datetime"]);
        }
        return $entries;
    }
}

?>