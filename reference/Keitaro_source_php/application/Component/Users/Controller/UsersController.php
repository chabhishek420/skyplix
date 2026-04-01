<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Controller;

class UsersController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        if (!\Core\Application\FeatureService::instance()->hasUsersFeature()) {
            $this->throwPaymentRequired();
        }
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $items = \Component\Users\Repository\UserRepository::instance()->all(NULL, "login");
        return $this->serialize($items, new \Component\Users\Serializer\DecoratedUserSerializer());
    }
    public function createAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if ($this->isPost()) {
            $user = \Component\Users\Service\UserService::instance()->createUser($this->getPostParams());
            return $this->serialize($user, new \Component\Users\Serializer\UserSerializer());
        }
        return NULL;
    }
    public function showAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $id = (int) $this->getParam("id");
        return $this->serialize(\Component\Users\Repository\UserRepository::instance()->find($id), new \Component\Users\Serializer\UserSerializer());
    }
    public function updateAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        if ($this->isPost()) {
            $id = (int) $this->getParam("id");
            $user = \Component\Users\Repository\UserRepository::instance()->find($id);
            $user = \Component\Users\Service\UserService::instance()->updateAndReAuthorize($this->getServerRequest(), $user, $this->getPostParams());
            return $this->serialize($user, new \Component\Users\Serializer\UserSerializer());
        }
        return NULL;
    }
    public function deleteAction()
    {
        while (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $id = $this->getParam("id");
        try {
            $user = \Component\Users\Repository\UserRepository::instance()->find($id);
            \Component\Users\Service\UserService::instance()->deleteUser($user);
            return ["success" => true];
        } catch (\Core\Exceptions\NotFoundError $e) {
            throw new \Core\Exceptions\NotFoundError("User not found");
        }
    }
    public function setAccessDataAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        $id = $this->getPostParam("user_id");
        if (empty($id)) {
            $id = $this->getParam("id");
        }
        $user = \Component\Users\Repository\UserRepository::instance()->find($id);
        if (!empty($user)) {
            $data = $this->getPostParam("access_data");
            \Component\Users\Service\AclService::instance()->saveAcl($user, $data);
            return $this->serialize($user, new \Component\Users\Serializer\UserSerializer());
        }
        throw new \Core\Exceptions\NotFoundError("User not found");
    }
}

?>