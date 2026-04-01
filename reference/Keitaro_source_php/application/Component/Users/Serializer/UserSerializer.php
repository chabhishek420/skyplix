<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Serializer;

class UserSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = ["id", "login", "type", "rules", "permissions"];
    protected $_keys = NULL;
    protected $_preferences = [];
    public function __construct($preferences = [])
    {
        $this->_preferences = $preferences;
    }
    public function extra($obj, $data)
    {
        $data["access_data"] = \Component\Users\Service\AclService::instance()->getByUserId($obj->getId());
        if (empty($data["access_data"])) {
            $data["access_data"] = new \stdClass();
        }
        $data["keyCount"] = \Component\Users\Repository\ApiKeysRepository::instance()->getUserKeyCount($obj->getId());
        $data["preferences"] = \Component\Users\Repository\UserPreferenceRepository::instance()->getPreferencesAsMap($obj, $this->_preferences);
        return $data;
    }
}

?>