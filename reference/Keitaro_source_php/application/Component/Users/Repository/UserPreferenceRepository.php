<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class UserPreferenceRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Users\Model\UserPreference::definition();
    }
    public function getAll(\Component\Users\Model\User $user, $only = [])
    {
        $where = [];
        $where[] = "user_id = " . \Core\Db\Db::quote($user->getId());
        if (!empty($only)) {
            $where[] = "pref_name IN (" . implode(",", \Core\Db\Db::quote($only)) . ")";
        }
        return $this->all(implode(" AND ", $where));
    }
    public function getPreferencesAsMap(\Component\Users\Model\User $user, $only = [])
    {
        $preferences = [];
        $items = $this->getAll($user, $only);
        foreach ($items as $preference) {
            $preferences[$preference->get("pref_name")] = $preference->get("pref_value");
        }
        if (empty($preferences["timezone"])) {
            $preferences["timezone"] = "UTC";
        }
        if (empty($preferences["language"])) {
            $preferences["language"] = "ru";
        }
        return $preferences;
    }
    public function get(\Component\Users\Model\User $user, $prefKey)
    {
        $where = [];
        $where[] = "user_id = " . \Core\Db\Db::quote($user->getId());
        $where[] = "pref_name = " . \Core\Db\Db::quote($prefKey);
        return \Core\Db\DataRepository::instance()->getOne($this->definition(), "pref_value", implode(" AND ", $where));
    }
}

?>