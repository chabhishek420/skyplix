<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Application;

class LicenseService extends \Traffic\Service\AbstractService
{
    private $_licenseIp = NULL;
    private $_domain = NULL;
    const SIGNATURE = "98353aac502b25a98c5724a16ae4fd15";
    public function setDomain($domain)
    {
        $this->_domain = $domain;
    }
    public function getDomain()
    {
        return $this->_domain;
    }
    public function setLicenseIp($licenseIp)
    {
        $this->_licenseIp = $licenseIp;
    }
    public function getLicenseIp()
    {
        return $this->_licenseIp;
    }
    public function isValidLicenseKey($key)
    {
        return preg_match("/^(?:[A-Z0-9]{4}-){3}[A-Z0-9]{4}\$/", $key);
    }
    public function saveLicenseKey($newKey)
    {
        if (self::instance()->isValidLicenseKey($newKey)) {
            $result = file_put_contents(EssentialService::instance()->getKeyFile(), $newKey);
            if ($result === false) {
                throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.save_key_error") . ": " . \Core\Locale\LocaleService::t("license.save_error_writing"));
            }
        }
    }
    public function getOpts()
    {
        return ["ip" => $this->getLicenseIp(), "key" => $this->getKey(), "version" => TDS_VERSION, "domain" => $this->getDomain(), "php" => phpversion(), "ioncube" => $this->getIoncubeVersion(), "tracker_id" => substr(md5(SALT), 2, 22), "uc" => $this->_getUsersCount()];
    }
    public function buildUri($opts)
    {
        $server = \Core\Security\ServerFinderService::instance()->findServer(\Core\Security\ServerFinderService::SERVER_LICENSE_TYPE);
        if (empty($server)) {
            throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.request_error"));
        }
        return $server["uri"] . $server["path"] . "?ip=" . $opts["ip"] . "&key=" . $opts["key"] . "&version=" . $opts["version"] . "&domain=" . $opts["domain"] . "&php=" . $opts["php"] . "&ioncube=" . $opts["ioncube"] . "&trid=" . $opts["tracker_id"] . "&uc=" . $opts["uc"];
    }
    public function getKey()
    {
        if (is_file(EssentialService::instance()->getKeyFile())) {
            return trim(file_get_contents(EssentialService::instance()->getKeyFile()));
        }
        return NULL;
    }
    public function getIoncubeVersion()
    {
        if (function_exists("ioncube_loader_version")) {
            return ioncube_loader_version();
        }
        return "";
    }
    private function _getUsersCount(string $_getUsersCount)
    {
        $count = (new \Component\System\Repository\TrackerInfoRepository())->getCached(\Component\System\Repository\TrackerInfoRepository::USERS_COUNT);
        if ($count) {
            return $count;
        }
        return 0;
    }
}

?>