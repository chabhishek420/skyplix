<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class SubId extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 140;
    const SUB_ID = "SubId";
    public function getType()
    {
        return TYPE_OTHER;
    }
    public function getField()
    {
        return NOTHING;
    }
    protected function _execute()
    {
        $subId = $this->getRawClick()->getSubId();
        if (isset($_GET["return"]) && $_GET["return"] == "jsonp") {
            $this->addHeader("Content-type: application/javascript; charset=utf-8");
            $subId = "KTracking.response(\"" . $subId . "\")";
        }
        $this->setContent($subId);
        $this->setDestinationInfo(SUB_ID . ": " . $subId);
    }
}

?>