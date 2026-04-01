<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class UserService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Users\Model\User::definition();
    }
    public function changePassword(\Component\Users\Model\User $user, $newPassword, $newPasswordConfirm, $dropSessions = true)
    {
        if ($newPassword != $newPasswordConfirm) {
            throw new \Core\Application\Exception\Error("users.passwords_not_equals");
        }
        $this->encodeUserPassword($user, $newPassword);
        UserService::instance()->save($user);
        if ($dropSessions) {
            AuthService::instance()->expireAllTokens($user);
        }
    }
    public function encodeUserPassword(\Component\Users\Model\User $user, $newPassword)
    {
        $user->set("password", AuthService::instance()->legacyEncodePassword($newPassword));
        $user->set("password_hash", AuthService::instance()->encodePasswordToHash($newPassword));
        return $user;
    }
    public function createUser($data)
    {
        $limit = \Core\Application\FeatureService::instance()->getUsersLimit();
        if ($limit && $limit <= \Component\Users\Repository\UserRepository::instance()->count()) {
            throw new \Core\Application\Exception\Error(\Core\Locale\LocaleService::t("users.limit_exceeded"));
        }
        if (empty($data["rules"])) {
            $data["rules"] = [];
        }
        $user = $this->build($data);
        if (isset($data["new_password"])) {
            if (empty($data["new_password_confirmation"]) || $data["new_password_confirmation"] != $data["new_password"]) {
                throw new \Core\Validator\ValidationError(["new_password" => [\Core\Locale\LocaleService::t("users.passwords_not_equal")]]);
            }
            $this->encodeUserPassword($user, $data["new_password"]);
        }
        $user = \Core\Db\DataService::instance()->create($this->definition(), $user);
        if (isset($data["preferences"])) {
            UserPreferenceService::instance()->updateAll($user, $data["preferences"]);
        }
        if (!$user->isAdmin()) {
            AclService::instance()->addDefaultAccess($user);
        }
        return $user;
    }
    public function updateAndReAuthorize(\Traffic\Request\ServerRequest $serverRequest, \Core\Entity\Model\EntityModelInterface $user, $data)
    {
        $isCurrentUser = CurrentUserService::instance()->get() && $user->getId() == CurrentUserService::instance()->get()->getId();
        if ($isCurrentUser && !AuthService::instance()->isUserPasswordCorrect($user, $data["current_password"])) {
            throw new \Core\Validator\ValidationError(["current_password" => [\Core\Locale\LocaleService::t("users.current_password_incorrect")]]);
        }
        if (isset($data["new_password"])) {
            $this->encodeUserPassword($user, $data["new_password"]);
        }
        $this->update($user, $data);
        if (!empty($data["preferences"])) {
            UserPreferenceService::instance()->updateAll($user, $data["preferences"]);
        }
        if ($isCurrentUser) {
            AuthService::instance()->findUserByLoginAndPassword($user->getLogin(), $data["new_password"], \Core\Application\FeatureService::instance()->hasUsersFeature());
        }
        AuthService::instance()->expireAllTokens($user);
        return $user;
    }
    public function deleteUser(\Core\Entity\Model\EntityModelInterface $user)
    {
        if ($this->_isDeletingLastAdmin($user)) {
            throw new \Core\Application\Exception\Error(\Core\Locale\LocaleService::t("users.you_cant_delete_supervisor"));
        }
        $this->delete($user);
    }
    private function _isDeletingLastAdmin(\Component\Users\Model\User $user)
    {
        return $user->isAdmin() && \Component\Users\Repository\UserRepository::instance()->getAdminCount() == 1;
    }
}

?>