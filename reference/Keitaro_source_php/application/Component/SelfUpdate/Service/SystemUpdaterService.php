<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\SelfUpdate\Service;

class SystemUpdaterService extends \Traffic\Service\AbstractService
{
    private $_cache = [];
    const MIN_RECOMMENDED_PHP_VERSION = "7.1";
    const CHANGELOG_URL = "https://keitaro.io/changelog.json";
    private function _isBeta()
    {
        return \Traffic\Repository\CachedSettingsRepository::instance()->get("is_beta_channel", false);
    }
    private function _getStabilityType()
    {
        if ($this->_isBeta()) {
            return "unstable";
        }
        return "stable";
    }
    public function getLastUpdateTimestamp()
    {
        return filemtime(ROOT . "/version.php");
    }
    public function isUpdateAvailable($version = TDS_VERSION)
    {
        return $this->getNewVersion($version);
    }
    public function isPHPOutdated()
    {
        return version_compare(phpversion(), MIN_RECOMMENDED_PHP_VERSION) < 0;
    }
    public function getNewVersion($trackerVersion = TDS_VERSION)
    {
        $data = $this->getUpdate(phpversion(), $trackerVersion);
        if (!$data || !isset($data["version"]) || version_compare($data["version"], $trackerVersion) == -1) {
            return false;
        }
        return $data["version"];
    }
    public function getReleaseInfoUrl($phpVersion, $trackerVersion)
    {
        $server = \Core\Security\ServerFinderService::instance()->findServer(\Core\Security\ServerFinderService::SERVER_UPDATE_TYPE);
        $url = $server["uri"] . "/license/api/getUpdate?version=" . $trackerVersion . "&stability=" . $this->_getStabilityType() . "&phpversion=" . $phpVersion . "&installation_method=" . $this->_getInstallationMethod() . "&kctl_version=" . \Component\System\Service\StatusService::instance()->getCurrentVersion();
        if (\Core\Application\Application::instance()->isDebug()) {
            $url .= "&debug=true";
        }
        return $url;
    }
    public function getUpdate($phpVersion, $trackerVersion)
    {
        while (!isset($this->_cache[$trackerVersion])) {
            $result = NULL;
            $uri = $this->getReleaseInfoUrl($phpVersion, $trackerVersion);
            try {
                $result = \Traffic\Http\Service\HttpService::instance()->get($uri, [], [\GuzzleHttp\RequestOptions::TIMEOUT => 0])->getBody();
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                if ($result) {
                    $this->_cache[$trackerVersion] = json_decode($result, true);
                } else {
                    $this->_cache[$trackerVersion] = NULL;
                }
            }
        }
        return $this->_cache[$trackerVersion];
    }
    public function getChangelog()
    {
        $content = \Traffic\Http\Service\HttpService::instance()->get($this->_getChangeLogUrl())->getBody();
        return json_decode($content);
    }
    private function _getChangeLogUrl()
    {
        preg_match("/^([0-9]+)\\./", TDS_VERSION, $match);
        $majorVersion = $match[1];
        return CHANGELOG_URL . "?from=" . $majorVersion . "&stability=" . $this->_getStabilityType();
    }
    private function _getInstallationMethod($_getInstallationMethod)
    {
        $version = \Component\System\Service\StatusService::instance()->getInstallationMethod();
        if (mb_stripos($version, \Component\System\Service\StatusService::APPROVED, NULL, "utf-8") === 0) {
            return mb_strtolower(\Component\System\Service\StatusService::APPROVED);
        }
        return mb_strtolower(\Component\System\Service\StatusService::CUSTOM);
    }
}

?>