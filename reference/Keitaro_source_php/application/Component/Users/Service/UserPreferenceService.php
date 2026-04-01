<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class UserPreferenceService extends \Traffic\Service\AbstractService
{
    public function update(\Component\Users\Model\User $user, $key, $value)
    {
        $where = [];
        $where[] = "user_id = " . \Core\Db\Db::quote($user->getId());
        $where[] = "`pref_name` = " . \Core\Db\Db::quote($key);
        $entry = \Component\Users\Model\UserPreference::findFirst(implode(" AND ", $where));
        if (empty($entry)) {
            $entry = \Component\Users\Model\UserPreference::build(["user_id" => $user->getId(), "pref_name" => $key]);
        }
        $entry->set("pref_value", $value);
        $entry->save();
        return $entry;
    }
    public function updateAll(\Component\Users\Model\User $user, $preferences)
    {
        if (is_array($preferences)) {
            foreach ($preferences as $name => $value) {
                $this->update($user, $name, $value);
            }
        }
    }
}

?>