<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class ApiKeysController extends \Admin\Controller\BaseController
{
    private function _findUser()
    {
        $userId = (int) $this->getParam("userId");
        if ($userId) {
            if (!$this->isAdmin()) {
                $this->throwDeny();
            }
            $user = \Component\Users\Model\User::find($userId);
        } else {
            $user = $this->getUser();
        }
        return $user;
    }
    public function getAllAction()
    {
        $user = $this->_findUser();
        $keys = \Component\Users\Repository\ApiKeysRepository::instance()->allByUser($user);
        return $this->serialize($keys, new \Component\Users\Serializer\ApiKeySerializer());
    }
    public function addAction()
    {
        $user = $this->_findUser();
        $key = \Component\Users\Service\ApiKeysService::instance()->addRandom($user);
        return $this->serialize($key, new \Component\Users\Serializer\ApiKeySerializer());
    }
    public function deleteAction()
    {
        $user = $this->_findUser();
        $keyId = (int) $this->getParam("keyId");
        $key = \Component\Users\Repository\ApiKeysRepository::instance()->findByUser($user, $keyId);
        \Core\Db\DataService::instance()->delete(\Component\Users\Model\ApiKey::definition(), $key);
    }
}

?>