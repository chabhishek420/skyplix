<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Triggers\Repository;

class TriggersRepository extends \Core\Entity\Repository\EntityRepository
{
    public static $_validTargets = NULL;
    public static $_validActions = NULL;
    public static $_validConditions = NULL;
    public function definition()
    {
        return \Component\Triggers\Model\TriggerAssociation::definition();
    }
    public static function getTargets()
    {
        return [\Component\Triggers\Model\TriggerAssociation::TARGET_STREAM => \Core\Locale\LocaleService::t("triggers.target_stream"), \Component\Triggers\Model\TriggerAssociation::TARGET_LANDINGS => \Core\Locale\LocaleService::t("triggers.target_landings"), \Component\Triggers\Model\TriggerAssociation::TARGET_OFFERS => \Core\Locale\LocaleService::t("triggers.target_offers"), \Component\Triggers\Model\TriggerAssociation::TARGET_SELECTED_PAGE => \Core\Locale\LocaleService::t("triggers.target_selected_page")];
    }
    public static function getConditions()
    {
        return [\Component\Triggers\Model\TriggerAssociation::CONDITION_NOT_RESPOND => \Core\Locale\LocaleService::t("triggers.condition_not_respond"), \Component\Triggers\Model\TriggerAssociation::CONDITION_CONTAINS => \Core\Locale\LocaleService::t("triggers.condition_contains"), \Component\Triggers\Model\TriggerAssociation::CONDITION_NOT_CONTAINS => \Core\Locale\LocaleService::t("triggers.condition_not_contains"), \Component\Triggers\Model\TriggerAssociation::CONDITION_AV_DETECTED => \Core\Locale\LocaleService::t("triggers.condition_av_detected"), \Component\Triggers\Model\TriggerAssociation::CONDITION_ALWAYS => \Core\Locale\LocaleService::t("triggers.condition_always")];
    }
    public static function getActions()
    {
        return [\Component\Triggers\Model\TriggerAssociation::ACTION_DISABLE => \Core\Locale\LocaleService::t("triggers.action_disable"), \Component\Triggers\Model\TriggerAssociation::ACTION_GRAB_FROM_PAGE => \Core\Locale\LocaleService::t("triggers.action_grab_from_page"), \Component\Triggers\Model\TriggerAssociation::ACTION_REPLACE_URL => \Core\Locale\LocaleService::t("triggers.action_replace_url"), \Component\Triggers\Model\TriggerAssociation::DO_NOTHING => \Core\Locale\LocaleService::t("triggers.do_nothing"), \Component\Triggers\Model\TriggerAssociation::WEBHOOK => \Core\Locale\LocaleService::t("triggers.webhook")];
    }
    public function findByStreamAndId(\Traffic\Model\BaseStream $stream, $id)
    {
        $where = "stream_id = " . (int) $stream->getId() . " AND id = " . (int) $id;
        return $this->findFirst($where);
    }
    public static function getValidConditions()
    {
        return self::$_validConditions;
    }
    public static function getValidActions()
    {
        return self::$_validActions;
    }
    public static function getValidTargets()
    {
        return self::$_validTargets;
    }
    public function allByStream(\Traffic\Model\BaseStream $stream)
    {
        return $this->all("stream_id = " . (int) $stream->getId(), "id");
    }
    protected function buildTargetContainer(\Component\Triggers\Model\TriggerAssociation $trigger, \Psr\Http\Message\UriInterface $uri)
    {
        return ["scan" => $trigger->shouldScanPage() ? \Component\Av\Service\AVCheckerService::PAGE : \Component\Av\Service\AVCheckerService::DOMAIN, "domain" => $uri->getHost(), "page" => (int) $uri];
    }
    public function getInfos(\Component\Triggers\Model\TriggerAssociation $trigger)
    {
        $result = [];
        $stream = \Component\Streams\Repository\StreamRepository::instance()->find($trigger->getStreamId());
        if (empty($stream)) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Triggers: skipped stream " . $trigger->getStreamId());
            return $result;
        }
        if ($stream->getActionType() == \Component\Av\Service\AVCheckerService::REMOTE) {
            $url = \Traffic\Http\Service\HttpService::instance()->get($stream->getActionPayload())->getBody();
            if (strpos($url, "http://") !== 0) {
                $url = $stream->getActionPayload();
            }
            $result[] = $this->buildTargetContainer($trigger, new \GuzzleHttp\Psr7\Uri($url));
        } else {
            if ($trigger->isTargetStream()) {
                $url = $stream->getActionPayload();
                $result[] = $this->buildTargetContainer($trigger, new \GuzzleHttp\Psr7\Uri($url));
            } else {
                if ($trigger->isTargetOffer()) {
                    $assocs = \Component\Streams\Repository\StreamOfferAssociationRepository::instance()->allByStream($stream);
                    foreach ($assocs as $assoc) {
                        $offer = \Component\Offers\Repository\OfferRepository::instance()->find($assoc->getOfferId());
                        $url = $offer->getActionPayload();
                        $result[] = $this->buildTargetContainer($trigger, new \GuzzleHttp\Psr7\Uri($url));
                    }
                } else {
                    if ($trigger->isTargetLanding()) {
                        $assocs = \Component\Streams\Repository\StreamLandingAssociationRepository::instance()->allByStream($stream);
                        foreach ($assocs as $assoc) {
                            $landing = \Component\Landings\Repository\LandingRepository::instance()->find($assoc->getLandingId());
                            $url = $landing->getActionPayload();
                            $result[] = $this->buildTargetContainer($trigger, new \GuzzleHttp\Psr7\Uri($url));
                        }
                    } else {
                        $url = $trigger->getSelectedPage();
                        $result[] = $this->buildTargetContainer($trigger, new \GuzzleHttp\Psr7\Uri($url));
                    }
                }
            }
        }
        return $result;
    }
    public function allWithActiveStream()
    {
        $groupBy = "t.id";
        $joins = [];
        $groupStreamTableName = \Traffic\Model\Stream::getTableName();
        $joins[] = "LEFT JOIN " . $groupStreamTableName . " as streams ON t.stream_id = streams.id";
        $groupCampaignTableName = \Traffic\Model\Campaign::getTableName();
        $joins[] = "LEFT JOIN " . $groupCampaignTableName . " as campaigns ON streams.campaign_id = campaigns.id";
        $select = "t.*, streams.state, campaigns.state";
        return $this->all("(t.next_run_at <= " . time() . " OR t.next_run_at IS NULL) AND streams.state <> \"deleted\" AND campaigns.state = \"active\"", "t.id", NULL, NULL, $select, $groupBy, $joins);
    }
}

?>