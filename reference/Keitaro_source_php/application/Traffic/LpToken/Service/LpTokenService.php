<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\LpToken\Service;

class LpTokenService extends \Traffic\Service\AbstractService
{
    private $_storage = NULL;
    const UUID_PREFIX = "uuid_";
    const DEFAULT_TTL = 86400;
    public function setStorage(\Traffic\LpToken\Storage\StorageInterface $storage)
    {
        $this->_storage = $storage;
    }
    public static function generateUserKey($postFix = NULL)
    {
        return hash("sha256", SALT) . $postFix;
    }
    public static function getTTL()
    {
        $ttl = intval(\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_OFFER_TOKEN_TTL, DEFAULT_TTL / 60)) * 60;
        if (!$ttl) {
            $ttl = DEFAULT_TTL;
        }
        return $ttl;
    }
    public function storeRawClick(\Traffic\RawClick $rawClick)
    {
        $token = $this->_generateToken($rawClick->getSubId());
        $encodedData = json_encode(\Traffic\Tools\Tools::utf8ize($rawClick->serialize()), JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (json_last_error()) {
            \Traffic\Logging\Service\LoggerService::instance()->error(function () {
                return "[LpTokenService] " . json_last_error_msg() . ": " . serialize($encodedData);
            });
        }
        $ttl = self::getTTL();
        $this->getStorage()->set($token, $encodedData, $ttl);
        return $token;
    }
    public function getRawClickByToken($token)
    {
        $encodedData = $this->getStorage()->get($token);
        if (empty($encodedData)) {
            return NULL;
        }
        $data = json_decode($encodedData, JSON_OBJECT_AS_ARRAY);
        if (json_last_error()) {
            \Traffic\Logging\Service\LoggerService::instance()->error("[LpTokenService] Can't decode json " . $encodedData . " (" . json_last_error_msg() . ")");
        }
        return new \Traffic\RawClick($data);
    }
    public function delete($token)
    {
        $this->getStorage()->delete($token);
    }
    public function getStorage()
    {
        if (empty($this->_storage)) {
            throw new \Exception("Storage for LpTokenService is not set yet");
        }
        return $this->_storage;
    }
    private function _generateToken($subId)
    {
        return UUID_PREFIX . $subId . "_" . uniqid($subId, true);
    }
    public function subIdFromToken($token)
    {
        $arr = explode("_", $token);
        if (2 < count($arr)) {
            return $arr[1];
        }
        return NULL;
    }
}

?>