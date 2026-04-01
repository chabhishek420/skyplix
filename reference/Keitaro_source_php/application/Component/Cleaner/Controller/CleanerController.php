<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Cleaner\Controller;

class CleanerController extends \Admin\Controller\BaseController
{
    public function cleanAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        if (!$this->isPost()) {
            return ["success" => false];
        }
        $timezone = $this->getParam("timezone");
        $startDate = $this->getParam("start_date");
        $endDate = $this->getParam("end_date");
        if (!$startDate || !$endDate) {
            return ["success" => false, "error" => \Core\Locale\LocaleService::t("cleaner.error")];
        }
        $this->_validateDate($startDate, $timezone);
        $this->_validateDate($endDate, $timezone);
        if ($campaignId = $this->getParam("campaign_id")) {
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
            if (!$this->isEditAllowed($campaign)) {
                $this->throwDeny();
            }
            $this->_schedule($startDate, $endDate, $timezone, $campaign->getId());
            return ["success" => true];
        }
        $user = $this->getUser();
        if ($user->isAdmin()) {
            $this->_schedule($startDate, $endDate, $timezone);
            return ["success" => true];
        }
        $campaignIds = \Component\Users\Repository\AclRuleRepository::instance()->getAllowedIdsByResourceName("campaigns", $this->getUser()->getId());
        if (!empty($campaignIds)) {
            foreach ($campaignIds as $campaignId) {
                $this->_schedule($startDate, $endDate, $timezone, $campaignId);
            }
        }
        return ["success" => true];
    }
    public function warmupCacheAction()
    {
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
    }
    private function _schedule($_schedule, $startDate, $endDate = NULL, $timezone = NULL, string $campaignId)
    {
        \Component\Cleaner\DelayedCommand\DeleteStatsCommand::schedule(["start_date" => $startDate, "end_date" => $endDate, "timezone" => $timezone, "campaign_id" => isset($campaignId) ? $campaignId : NULL]);
    }
    private function _validateDate($_validateDate, $date = NULL, $timezone)
    {
        try {
            if (empty($timezone)) {
                $tz = new \DateTimeZone("UTC");
            } else {
                $tz = new \DateTimeZone($timezone);
            }
            new \DateTime($date, $tz);
            return true;
        } catch (\Exception $e) {
            throw new \Core\Validator\ValidationError(["success" => false, "error" => \Core\Locale\LocaleService::t("cleaner.error")]);
        }
    }
}

?>