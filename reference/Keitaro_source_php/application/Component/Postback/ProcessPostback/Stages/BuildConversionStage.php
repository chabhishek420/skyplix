<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback\Stages;

class BuildConversionStage implements \Component\Postback\ProcessPostback\StageInterface
{
    public function process(\Component\Postback\ProcessPostback\Payload $payload)
    {
        if ($payload->isStatusChange()) {
            return NULL;
        }
        $postback = $payload->getPostback();
        $click = $payload->getClick();
        $offer = $payload->getOffer();
        $newConversion = \Traffic\Model\Conversion::build(["campaign_id" => $click->get("campaign_id"), "stream_id" => $click->get("stream_id"), "offer_id" => $click->getOfferId(), "ts_id" => $click->get("ts_id"), "landing_id" => $click->get("landing_id"), "affiliate_network_id" => $offer ? $offer->getAffiliateNetworkId() : NULL, "sub_id" => $click->getSubId(), "tid" => $postback->getTid()]);
        $payload->setNewConversion($newConversion);
    }
}

?>