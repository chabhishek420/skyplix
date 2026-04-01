<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\Repository;

class TrackerInfoRepository
{
    const USERS_COUNT = "users_count";
    public function info($info)
    {
        return [USERS_COUNT => $this->_getUsersCount()];
    }
    public function getCached($key)
    {
        try {
            $data = \Traffic\CachedData\Repository\CachedDataRepository::instance()->get(\Traffic\CachedData\DataGetter\GetTrackerInfo::NAME);
            if (isset($data[$key])) {
                return $data[$key];
            }
            return NULL;
        } catch (\Exception $e) {
        }
    }
    private function _getUsersCount(string $_getUsersCount)
    {
        return \Component\Users\Repository\UserRepository::instance()->count() - 1;
    }
}

?>