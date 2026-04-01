<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class ApiKeysService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Users\Model\ApiKey::definition();
    }
    public function addRandom(\Component\Users\Model\User $user)
    {
        $newKey = $this->_generateRandom();
        $key = $this->create(["key" => $newKey, "user_id" => $user->getId(), "datetime" => new \Datetime()]);
        return $key;
    }
    private function _generateRandom()
    {
        return md5(uniqid(rand(), true) . SALT);
    }
}

?>