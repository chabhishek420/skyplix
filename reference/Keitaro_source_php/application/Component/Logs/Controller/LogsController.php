<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Logs\Controller;

class LogsController extends \Admin\Controller\BaseController
{
    const SYSTEM_LOG = "system";
    const TRAFFIC_LOG = "traffic";
    const POSTBACK_LOG = "postbacks";
    const ENABLE_SSL_LOG = "enable_ssl";
    public function systemAction()
    {
        $this->_isActionAllowed();
        list($limit, $offset, $query) = $this->_getQueryParams();
        return \Traffic\Logging\Service\LoggerService::instance()->getRows($offset, $limit, \Component\Users\Service\CurrentUserService::instance()->getTimezone(), $query);
    }
    public function deleteSystemAction()
    {
        $this->_isActionAllowed();
        \Traffic\Logging\Service\LoggerService::instance()->cleanLog();
    }
    public function postbacksAction()
    {
        $this->_isActionAllowed();
        list($limit, $offset, $query) = $this->_getQueryParams();
        return \Core\Logging\Service\PostbackLoggerService::instance()->getRows($offset, $limit, \Component\Users\Service\CurrentUserService::instance()->getTimezone(), $query);
    }
    public function trafficAction()
    {
        list($limit, $offset, $query) = $this->_getQueryParams();
        $campaignId = $this->getParam("campaign_id");
        $this->_isActionAllowed($campaignId);
        if (!empty($campaignId)) {
            $query .= " && Processing campaign " . $campaignId;
            $query = trim($query, " &&");
        }
        return \Traffic\Logging\Service\TrafficLoggerService::instance()->getRows($offset, $limit, \Component\Users\Service\CurrentUserService::instance()->getTimezone(), $query);
    }
    public function deleteTrafficAction()
    {
        $this->_isActionAllowed();
        \Traffic\Logging\Service\TrafficLoggerService::instance()->cleanLog();
    }
    public function sentPostbacksAction()
    {
        $this->_isActionAllowed();
        list($limit, $offset, $query) = $this->_getQueryParams();
        return \Core\Logging\Service\SentPostbackLoggerService::instance()->getRows($offset, $limit, \Component\Users\Service\CurrentUserService::instance()->getTimezone(), $query);
    }
    public function deletePostbacksAction()
    {
        $this->_isActionAllowed();
        \Core\Logging\Service\PostbackLoggerService::instance()->cleanLog();
    }
    public function deleteSentPostbacksAction()
    {
        $this->_isActionAllowed();
        \Core\Logging\Service\SentPostbackLoggerService::instance()->cleanLog();
    }
    public function enableSSLAction()
    {
        $this->_isActionAllowed();
        list($limit, $offset, $query) = $this->_getQueryParams();
        return \Component\Domains\Service\DomainSSLLogsService::instance()->getRows();
    }
    public function enableSSLLogFileAction()
    {
        $this->_isActionAllowed();
        $filename = $this->getParam("filename");
        if (!$filename) {
            $this->throwError(\Core\Locale\LocaleService::t("exceptions.bad_request"));
        }
        $content = \Component\Domains\Service\DomainSSLLogsService::instance()->getFileContents($filename);
        if ($content === false) {
            throw new \Traffic\Request\ServerRequestError(\Core\Locale\LocaleService::t("exceptions.bad_request"));
        }
        return ["data" => $content];
    }
    public function deleteEnableSSLAction()
    {
        $this->_isActionAllowed();
        \Component\Domains\Service\DomainSSLLogsService::instance()->cleanLog();
    }
    public function typesAction()
    {
        return [SYSTEM_LOG, TRAFFIC_LOG, POSTBACK_LOG, ENABLE_SSL_LOG];
    }
    private function _isActionAllowed($campaignId = NULL)
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!empty($campaignId)) {
            $campaign = \Component\Campaigns\Repository\CampaignRepository::instance()->find($campaignId);
            if ($this->isViewAllowed($campaign)) {
                return true;
            }
        }
        $this->throwDeny();
        return false;
    }
    private function _getQueryParams()
    {
        return [$limit = $this->getParam("limit"), $offset = $this->getParam("offset"), $query = $this->getParam("query")];
    }
}

?>