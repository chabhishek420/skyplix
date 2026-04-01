<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback;

class PayloadFactory
{
    public static function produce(\Component\Postback\Postback $postback)
    {
        $clicks = \Component\Clicks\Repository\ClickRepository::instance()->findByPostback($postback);
        if (empty($clicks)) {
            $subId = $postback->getSubId();
            if (empty($subId)) {
                throw new \Component\Postback\PostbackError(\Core\Locale\LocaleService::t("conversions.sub_id_empty"));
            }
            throw new \Component\Postback\PostbackError(\Core\Locale\LocaleService::t("conversions.sub_id_not_found", $subId));
        }
        $payloads = [];
        $offerDic = [];
        foreach ($clicks as $click) {
            $payloads[$click->getSubId()] = new Payload($postback, $click);
            $clickOfferId = $click->getOfferId();
            if (!empty($clickOfferId)) {
                $offerDic[$clickOfferId] = $click->getSubId();
            }
        }
        $conversions = \Component\Conversions\Repository\ConversionRepository::instance()->findBySubIds(array_keys($payloads));
        foreach ($conversions as $conversion) {
            $subId = $conversion->getSubId();
            $payloads[$subId]->addOldConversion($conversion);
        }
        foreach ($payloads as $payload) {
            $payload->updateOldConversionsTid();
        }
        $offerIds = array_keys($offerDic);
        if (!empty($offerIds)) {
            $offers = \Component\Offers\Repository\OfferRepository::instance()->allByIds($offerIds);
            foreach ($offers as $offer) {
                $payloads[$offerDic[$offer->getId()]]->setOffer($offer);
            }
        }
        return $payloads;
    }
}

?>