<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Application;

class EssentialService extends \Traffic\Service\AbstractService
{
    private $_cachedTokenFile = NULL;
    private $_keyFile = NULL;
    private $_testStub = NULL;
    const ATTEMPTS_COUNT = 2;
    const REFRESH_LICENSE_CHANCE = 10;
    const SIGNATURE = "9012hefg02uh3ef0u123e0fuhwidjnsc0uhPISUHhsuh93iehv";
    const ESSENTIAL_LOCK = "essential.lock";
    const CONNECTION_TIMEOUT = 7;
    const REQUEST_TIMEOUT = 20;
    const X_V8 = "-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA7KyuqzZrXs8D8xkCxxtl\nMGzya6YBLMMKoJbwjCnOTznpaRvOTZD80Uz0U78AaY8RVVl4pxPJHEvFUUOW+IGM\nls2JTItgGVeA3mYzeFwn4ISQKd+rG35Qs/QIei1K/Ksyp/OY6VYSju3+6L7OATVJ\nOczungurzWlA/CWCHdQ1ROA0qHzs/XeRyHXN//rmz6SPkkYkXVtE7PQHl40FkraS\nAAamUiU0X5wmT82djUOCkBJS1UT3LNXmW7JBkEbxopTWvQICy7vEWSCZLczuO7Gd\nOEShumCla+jxQWUpQOjxlbyk0wXnSj0vbXNidA7sIBpN9MZpOj49/iewer4ePwmH\nhQIDAQAB\n-----END PUBLIC KEY-----";
    const ALG_V8 = "RS512";
    const CACHE_FILE_TTL = 2;
    const SUCCESS_CODE = 200;
    const NO_LICENSE_CODE = 402;
    public function __construct()
    {
        $this->setKeyFile(ROOT . "/var/license/key.lic");
        $this->setCachedTokenFile(ROOT . "/var/license/hash.lic");
    }
    public function stubFutureToken($token)
    {
        $this->_testStub = $token;
    }
    public function loadFeatures()
    {
        try {
            $payload = $this->getCachedPayload();
        } catch (Exception\LicenseError $e) {
            $payload = new EssentialPayload();
            FeatureService::instance()->init($payload);
            return $payload;
        }
    }
    public function checkIfTokenUpdated()
    {
        $payload = $this->getCachedPayload();
        if ($this->_mustBeForcedRefreshed() || $this->payloadContainsError($payload)) {
            $payload = $this->refreshToken();
        }
        return $payload;
    }
    public function validate(EssentialPayload $payload)
    {
        if ($error = $this->_findPayloadError($payload)) {
            throw new Exception\LicenseError($error);
        }
        return true;
    }
    public function payloadContainsError(EssentialPayload $payload)
    {
        $error = $this->_findPayloadError($payload);
        return $error;
    }
    private function _findPayloadError(EssentialPayload $payload)
    {
        if ($payload->isTokenExpired()) {
            return "token expired";
        }
        if ($payload->errorMessage()) {
            return $payload->errorMessage();
        }
        if (!$payload->ip()) {
            return \Core\Locale\LocaleService::t("license.incorrect_token");
        }
        if ($payload->ip() != LicenseService::instance()->getLicenseIp()) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Validate: " . $payload->ip() . "!=" . LicenseService::instance()->getLicenseIp());
            return \Core\Locale\LocaleService::t("license.incorrect_ip");
        }
        if ($payload->isLicenseExpired()) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Validate: expires " . $payload->licenseExpired());
            return \Core\Locale\LocaleService::t("license.license_expired");
        }
        if ($payload->key() != LicenseService::instance()->getKey()) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Validate: " . $payload->key() . "!=" . LicenseService::instance()->getKey());
            return \Core\Locale\LocaleService::t("license.incorrect_key");
        }
        return NULL;
    }
    public function setKeyFile($path)
    {
        $this->_keyFile = $path;
    }
    public function getKeyFile()
    {
        return $this->_keyFile;
    }
    public function setCachedTokenFile($path)
    {
        $this->_cachedTokenFile = $path;
    }
    public function getCachedTokenFile()
    {
        return $this->_cachedTokenFile;
    }
    public function getCachedPayload()
    {
        $file = $this->getCachedTokenFile();
        if (file_exists($this->getCachedTokenFile())) {
            if (!is_writable($file)) {
                throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.save_error"));
            }
            $lockService = \Core\Lock\LockService::instance();
            $lock = $lockService->waitForLock(ESSENTIAL_LOCK);
            $token = file_get_contents($file);
            $lockService->unlock($lock, ESSENTIAL_LOCK);
            return $this->decodeToken($token, true);
        }
        return new EssentialPayload();
    }
    public function requestToken($opts)
    {
        $uri = LicenseService::instance()->buildUri($opts);
        return $this->_securedRequest($uri);
    }
    public function decodeToken($token)
    {
        while (empty($token)) {
            try {
                $data = (int) \Core\Sentinel\Single::decode($token, X_V8, [ALG_V8]);
                return new EssentialPayload($data);
            } catch (\UnexpectedValueException $e) {
                throw new Exception\LicenseError("The license code can't be decoded");
            } catch (\Exception $e) {
                throw new Exception\LicenseError("The license code can't be decoded");
            }
        }
        return new EssentialPayload([]);
    }
    public function checkLicenseKey($checkLicenseKey, $key)
    {
        $params = LicenseService::instance()->getOpts();
        $params["key"] = $key;
        try {
            $payload = $this->_requestAndValidateToken($params);
            $this->validate($payload);
            if ($payload->isCorrect() && !$payload->isLicenseExpired()) {
                return true;
            }
            throw new Exception\LicenseError($payload->errorMessage());
        } catch (Exception\NoLicenseError $e) {
            throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.license_expired"));
        } catch (\Exception $e) {
            throw new Exception\LicenseError($e->getMessage());
        }
    }
    public function refreshToken()
    {
        $i = 0;
        while ($i < ATTEMPTS_COUNT) {
            try {
                $params = LicenseService::instance()->getOpts();
                return $this->_requestAndValidateToken($params);
            } catch (Exception\NoLicenseError $error) {
                unlink($this->getCachedTokenFile());
                $i++;
            }
        }
        throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.request_error"));
    }
    private function _requestAndValidateToken(EssentialPayload $_requestAndValidateToken, $params)
    {
        $token = $this->requestToken($params);
        $payload = $this->decodeToken($token);
        if ($this->tokenWithPayloadShouldBeSaved($payload)) {
            TsService::instance()->saveTimestamp($payload->licenseExpired());
            $this->saveToken($token);
        }
        return $payload;
    }
    public function saveToken($token)
    {
        $lockService = \Core\Lock\LockService::instance();
        $lock = $lockService->waitForLock(ESSENTIAL_LOCK);
        try {
            if (file_exists($this->getCachedTokenFile()) && !is_writable($this->getCachedTokenFile())) {
                throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.save_error") . ": " . \Core\Locale\LocaleService::t("license.save_error_not_writable"));
            }
            if (!file_put_contents($this->getCachedTokenFile(), $token)) {
                throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.save_error") . ": " . \Core\Locale\LocaleService::t("license.save_error_writing"));
            }
            if (file_get_contents($this->getCachedTokenFile()) != $token) {
                throw new Exception\LicenseError(\Core\Locale\LocaleService::t("license.save_error") . ": " . \Core\Locale\LocaleService::t("license.save_error_wrong_content"));
            }
        } finally {
            $lockService->unlock($lock, ESSENTIAL_LOCK);
        }
    }
    public function tokenWithPayloadShouldBeSaved(EssentialPayload $payload)
    {
        if ($payload->error() == EssentialPayload::NO_LICENSE) {
            return true;
        }
        if (!$payload->key() || !$payload->ip()) {
            return false;
        }
        return true;
    }
    private function _mustBeForcedRefreshed()
    {
        if (Application::instance()->isProduction() && rand(0, 1000) <= REFRESH_LICENSE_CHANCE) {
            return true;
        }
        if (!file_exists($this->getCachedTokenFile())) {
            return true;
        }
        if (TsService::SECONDS_IN_DAY * CACHE_FILE_TTL < filemtime($this->getCachedTokenFile()) - time()) {
            return true;
        }
        return false;
    }
    private function _securedRequest($uri)
    {
        if (Application::instance()->isTesting()) {
            return $this->_testStub;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTP_VERSION, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, REQUEST_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, CONNECTION_TIMEOUT);
        curl_setopt($ch, CURLOPT_USERAGENT, "Keitaro Secured HTTPClient");
        curl_setopt($ch, CURLOPT_URL, $uri);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code == NO_LICENSE_CODE) {
            throw new Exception\NoLicenseError("License code " . $code);
        }
        if ($code != SUCCESS_CODE) {
            throw new Exception\LicenseError("License code " . $code . ", " . curl_error($ch));
        }
        curl_close($ch);
        return $data;
    }
}

?>