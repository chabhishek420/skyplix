<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\Controller;

class SystemController extends \Admin\Controller\BaseController
{
    public function refreshLicenseAction()
    {
        try {
            \Core\Application\EssentialService::instance()->refreshToken();
            \Core\Application\EssentialService::instance()->loadFeatures();
        } catch (\Core\Application\Exception\LicenseError $e) {
            $this->throwError($e->getMessage());
            return ["expires_at" => \Core\Application\FeatureService::instance()->getLicenseExpireTime()->format(\DateTimeInterface::ISO8601), "edition" => \Core\Application\FeatureService::instance()->getEdition()];
        }
    }
    public function licenseInfoAction()
    {
        $count = \Component\Users\Repository\UserRepository::instance()->getAdminCount();
        $type = $count == 0 ? \Component\CommonErrorHandler\CommonErrorHandler::TYPE_GREETING : \Component\CommonErrorHandler\CommonErrorHandler::TYPE_EXPIRES;
        if (0 < $count && !$this->isAdmin()) {
            $this->throwDeny();
        }
        \Core\Locale\LocaleService::instance()->getLanguage() ? exit : \Core\Locale\LocaleService::DEFAULT_LANGUAGE;
    }
    public function addLicenseKeyAction()
    {
        $count = \Component\Users\Repository\UserRepository::instance()->getAdminCount();
        if (0 < $count) {
            return ["success" => false, "message" => "Admin already exist"];
        }
        $key = $this->getParam("key");
        $language = $this->getParam("language");
        if (empty($key)) {
            return ["success" => false];
        }
        $key = strtoupper($key);
        try {
            \Core\Application\LicenseService::instance()->saveLicenseKey($key);
            \Core\Application\EssentialService::instance()->checkLicenseKey($key);
            \Core\Application\EssentialService::instance()->refreshToken();
            $password = \Traffic\Tools\Tools::generateRandomString(16);
            $language ? exit : \Core\Locale\LocaleService::DEFAULT_LANGUAGE;
        } catch (\Core\Application\Exception\LicenseError $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    public function changeLicenseKeyAction()
    {
        while (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $key = $this->getParam("key");
        $key = strtoupper($key);
        try {
            \Core\Application\LicenseService::instance()->saveLicenseKey($key);
            \Core\Application\EssentialService::instance()->checkLicenseKey($key);
            \Core\Application\EssentialService::instance()->refreshToken();
            return ["success" => true];
        } catch (\Core\Application\Exception\LicenseError $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }
    public function loadLanguageAction()
    {
        $language = $this->getParam("language");
        $parent = $this->getParam("parent");
        return ["translation" => \Core\Locale\LocaleService::instance()->getTranslations($language, $parent)];
    }
}

?>