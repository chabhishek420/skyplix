<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class SendToCampaignException extends \Exception
{
    private $_campaignId = NULL;
    public function setCampaignId($id)
    {
        $this->_campaignId = $id;
    }
    public function getCampaignId()
    {
        return $this->_campaignId;
    }
}

?>