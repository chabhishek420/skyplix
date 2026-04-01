<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Filter;

class Uniqueness extends \Core\Filter\AbstractFilter
{
    const UNIQUE_STREAM = "stream";
    const UNIQUE_GROUP = "group";
    const UNIQUE_CAMPAIGN = "campaign";
    const UNIQUE_GLOBAL = "global";
    public function getGroup()
    {
        return "filters.groups.device";
    }
    public function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick)
    {
        $isUnique = false;
        $filter->getPayload();
        switch ($filter->getPayload()) {
            case UNIQUE_STREAM:
                $stream = \Traffic\Repository\CachedStreamRepository::instance()->findCachedStream($filter->getStreamId());
                $campaign = \Traffic\Repository\CachedCampaignRepository::instance()->findInCacheById($stream->getCampaignId());
                $isUnique = \Traffic\Session\Service\UniquenessSessionService::instance()->isUniqueForStream($this->getServerRequest(), $rawClick, $campaign, $stream);
                break;
            case UNIQUE_GROUP:
            case UNIQUE_CAMPAIGN:
                $isUnique = $rawClick->isUniqueCampaign();
                break;
            case UNIQUE_GLOBAL:
                $isUnique = $rawClick->isUniqueGlobal();
                break;
            default:
                return $isUnique && $filter->getMode() == \Traffic\Model\StreamFilter::ACCEPT || !$isUnique && $filter->getMode() == \Traffic\Model\StreamFilter::REJECT;
        }
    }
    public function getHeaderTemplate()
    {
        return "<select class=\"form-control\" ng-model=\"filter.payload\">\n        <option value=\"stream\">" . \Core\Locale\LocaleService::t("filters.uniqueness_filter_scope.stream") . "</option>\n        <option value=\"campaign\">" . \Core\Locale\LocaleService::t("filters.uniqueness_filter_scope.campaign") . "</option>\n        <option value=\"global\">" . \Core\Locale\LocaleService::t("filters.uniqueness_filter_scope.global") . "</option>\n        </select>";
    }
    public function getDefaults()
    {
        return UNIQUE_STREAM;
    }
}

?>