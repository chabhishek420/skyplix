<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class ProfileController extends \Admin\Controller\BaseController
{
    public function currentAccessAction()
    {
        return ["access_data" => \Component\Users\Service\AclService::instance()->getByUserId($this->getUser()->getId())];
    }
    public function showAction()
    {
        return $this->serialize($this->getUser(), new \Component\Users\Serializer\UserSerializer());
    }
    public function updateAction()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        $user = $this->getUser();
        $newPassword = $this->getPostParam("new_password");
        if (!empty($newPassword)) {
            if (!\Component\Users\Service\AuthService::instance()->isUserPasswordCorrect($user, $this->getPostParam("current_password"))) {
                throw new \Core\Application\Error("users.current_password_incorrect");
            }
            \Component\Users\Service\UserService::instance()->changePassword($user, $this->getPostParam("new_password"), $this->getPostParam("new_password_confirmation"));
        }
        \Component\Users\Service\UserPreferenceService::instance()->updateAll($user, $this->getPostParam("preferences"));
        return $this->showAction();
    }
    public function languagesAction()
    {
        return [["value" => "ru", "name" => \Core\Locale\LocaleService::t("profile.languages.ru")], ["value" => "en", "name" => \Core\Locale\LocaleService::t("profile.languages.en")]];
    }
    public function timezonesAction()
    {
        return \Component\Users\Repository\TimezoneRepository::instance()->listAsOptions();
    }
}

?>