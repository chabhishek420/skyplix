<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class UserRepository extends \Core\Entity\Repository\EntityRepository
{
    private $_timezone = NULL;
    public function definition()
    {
        return \Component\Users\Model\User::definition();
    }
    public function getFirstAdminTimezone()
    {
        if (empty($this->_timezone)) {
            $user = $this->findFirst("type = " . \Core\Db\Db::quote(\Component\Users\Model\User::TYPE_ADMIN));
            if ($user) {
                $this->_timezone = UserPreferenceRepository::instance()->get($user, "timezone");
            }
            if (empty($this->_timezone)) {
                $this->_timezone = "UTC";
            }
            $this->_timezone = new \DateTimeZone($this->_timezone);
        }
        return $this->_timezone;
    }
    public function getAdminCount()
    {
        return $this->count("type = " . \Core\Db\Db::quote(\Component\Users\Model\User::TYPE_ADMIN));
    }
}

?>