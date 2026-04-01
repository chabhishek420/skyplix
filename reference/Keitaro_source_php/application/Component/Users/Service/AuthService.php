<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class AuthService extends \Traffic\Service\AbstractService
{
    public $forceLegacy = false;
    public $forceSingleHash = false;
    const VERSION_BCRYPT = "v1";
    const LOGIN_KEY = "login";
    const PASS_KEY = "password";
    const TIMESTAMP_KEY = "timestamp";
    const POSTFIX_TOKEN_KEY = "_get_for_auth";
    const COOKIE_PARAM = "states";
    public function expireAllTokens(\Component\Users\Model\User $user)
    {
        \Core\Db\DataService::instance()->deleteAll(\Component\Users\Model\UserPasswordHash::definition(), "user_id = " . \Core\Db\Db::quote($user->getId()));
    }
    public function clearCookieToken(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response)
    {
        return \Traffic\Cookies\Service\CookiesService::instance()->unsetCookie($serverRequest, $response, COOKIE_PARAM);
    }
    public function loadFromCookieToken(\Traffic\Request\ServerRequest $serverRequest)
    {
        $token = $serverRequest->getCookieParam(COOKIE_PARAM);
        if (empty($token)) {
            return false;
        }
        $user = $this->_tryToLoadFromToken($serverRequest);
        if ($user) {
            CurrentUserService::instance()->set($user);
        }
        return $user;
    }
    public function isUserPasswordCorrect(\Component\Users\Model\User $user, $password)
    {
        $hash = $user->getPasswordHash();
        if (!empty($hash)) {
            if (password_verify($password, $hash)) {
                return true;
            }
        } else {
            if ($user->getPassword() == $this->legacyEncodePassword($password)) {
                return true;
            }
        }
        return false;
    }
    public function findUserByLoginAndPassword($login, $password, $isFeatureAvailable)
    {
        if ($user = $this->_findByLoginAndPassword($login, $password)) {
            if (!$isFeatureAvailable && $user->getType() != \Component\Users\Model\User::TYPE_ADMIN) {
                throw new \Core\Application\Exception\EditionError();
            }
            return $user;
        }
        return NULL;
    }
    protected function _bcryptColumnExists()
    {
        return \Core\Db\DataRepository::instance()->columnExists(\Component\Users\Model\User::definition(), "password_hash") && !$this->forceLegacy;
    }
    protected function _userPasswordHashExists()
    {
        return \Core\Db\DataRepository::instance()->tableExists(\Component\Users\Model\UserPasswordHash::definition()) && !$this->forceSingleHash;
    }
    protected function _findByLoginAndPassword($login, $password)
    {
        $legacyPassword = $this->legacyEncodePassword($password);
        $users = \Component\Users\Repository\UserRepository::instance()->all("`login` = " . \Core\Db\Db::quote($login));
        foreach ($users as $user) {
            $oldPassword = $user->get("password");
            $passwordHash = $user->get("password_hash");
            if (empty($passwordHash) && $oldPassword == $legacyPassword || password_verify($password, $passwordHash)) {
                $newPasswordHash = $this->encodePasswordToHash($password);
                $user->set("password_hash", $newPasswordHash);
                \Core\Db\DataService::instance()->save(\Component\Users\Model\User::definition(), $user);
                return $user;
            }
        }
        return false;
    }
    protected function _storeHash(\Component\Users\Model\User $user)
    {
        if ($this->_userPasswordHashExists()) {
            $userId = $user->getId();
            $passwordHash = $user->getPasswordHash();
            $expiresAt = new \Datetime();
            $expiresAt->add(new \DateInterval("PT" . $this->_getExpireSeconds() . "S"));
            $hash = \Component\Users\Model\UserPasswordHash::build(["user_id" => $userId, "password_hash" => $passwordHash, "expires_at" => $expiresAt]);
            $hash->save();
        }
    }
    public function storeSession(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, \Component\Users\Model\User $user)
    {
        $this->_storeHash($user);
        $login = $user->getLogin();
        $passwordHash = $user->getPasswordHash();
        $login_hash = md5($login . "-keitaro");
        $date = new \DateTime();
        $authJson = [LOGIN_KEY => $login_hash, PASS_KEY => urlencode($passwordHash), TIMESTAMP_KEY => $date->getTimestamp()];
        $token = VERSION_BCRYPT . \Firebase\JWT\JWT::encode($authJson, \Traffic\LpToken\Service\LpTokenService::generateUserKey(POSTFIX_TOKEN_KEY), "HS256");
        return $this->_storeSystemCookieWithTTL($serverRequest, $response, $token);
    }
    protected function _tryToLoadFromToken(\Traffic\Request\ServerRequest $serverRequest)
    {
        $tmp = substr($serverRequest->getCookieParam(COOKIE_PARAM), strlen(VERSION_BCRYPT));
        try {
            $decodedData = (int) \Firebase\JWT\JWT::decode($tmp, \Traffic\LpToken\Service\LpTokenService::generateUserKey(POSTFIX_TOKEN_KEY), ["HS256"]);
            $login_hash = $decodedData[LOGIN_KEY];
            $password_hash = urldecode($decodedData[PASS_KEY]);
            $timestamp = $decodedData[TIMESTAMP_KEY];
            $now = new \DateTime();
            if ($this->_getExpireSeconds() < $now->getTimestamp() - $timestamp) {
                return NULL;
            }
            $user = NULL;
            if ($this->_userPasswordHashExists()) {
                $user = \Component\Users\Repository\UserRepository::instance()->findFirst("MD5(CONCAT(`login`, '-keitaro')) = " . \Core\Db\Db::quote($login_hash) . " and id in (SELECT user_id " . " FROM " . \Component\Users\Model\UserPasswordHash::getTableName() . " WHERE password_hash = " . \Core\Db\Db::quote($password_hash) . " AND expires_at > NOW())");
            } else {
                $sql = "SELECT * FROM " . \Core\Db\Db::getPrefix() . "users \n                WHERE MD5(CONCAT(`login`, '-keitaro')) = " . \Core\Db\Db::quote($login_hash) . "\n                AND BINARY `password_hash` = " . \Core\Db\Db::quote($password_hash);
                $data = \Core\Db\Db::instance()->getRow($sql);
                if (isset($data) && isset($data["id"])) {
                    $user = new \Component\Users\Model\User();
                    $user->restoreData($data);
                }
            }
            return $user;
        } catch (\Exception $e) {
        }
    }
    protected function _storeSystemCookieWithTTL(\Traffic\Request\ServerRequest $serverRequest, \Traffic\Response\Response $response, $token)
    {
        return \Traffic\Cookies\Service\CookiesService::instance()->setRaw($serverRequest, $response, COOKIE_PARAM, $token, $this->_getExpireSeconds(), false, NULL);
    }
    protected function _getExpireSeconds()
    {
        return 2678400;
    }
    protected function _startsWith($haystack, $needle)
    {
        return substr($haystack, 0, strlen($needle)) == $needle;
    }
    public function encodePasswordToHash($string)
    {
        return password_hash($string, PASSWORD_BCRYPT);
    }
    public function legacyEncodePassword($string)
    {
        return md5($string . SALT);
    }
}

?>