<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class ToCampaign extends \Traffic\Actions\AbstractAction
{
    protected $_weight = 6;
    public function getType()
    {
        return TYPE_OTHER;
    }
    public function getField()
    {
        return CAMPAIGNS;
    }
    protected function _execute()
    {
        $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($this->getRawActionPayload());
        if (!empty($campaign)) {
            $this->setDestinationInfo($campaign->getName());
        }
    }
}

?>