<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\QueryParams;

class UserParams
{
    private $_allowedCampaignIds = NULL;
    private $_restrictedColumns = NULL;
    private $_timezone = NULL;
    public static function create(\Admin\Controller\BaseController $controller)
    {
        $user = $controller->getUser();
        $allowedCampaignIds = \Component\Users\Service\AclService::instance()->getAllowedCampaignIds($user);
        $restrictedColumns = \Component\Users\Service\AclService::instance()->getRestrictedReportColumns($user);
        $timezone = \Component\Users\Repository\UserPreferenceRepository::instance()->get($user, \Component\Users\Model\UserPreference::TIMEZONE);
        return new UserParams($user, $allowedCampaignIds, $restrictedColumns, $timezone);
    }
    public function __construct(\Component\Users\Model\User $user, $allowedCampaignIds = \Component\Users\Service\AclService::ALLOW_ANY, $restrictedColumns = [], $timezone = "Europe/Moscow")
    {
        if (empty($user) || !$user instanceof \Component\Users\Model\User) {
            throw new Error("You must provide \"user\"");
        }
        $this->_allowedCampaignIds = $allowedCampaignIds;
        $this->_restrictedColumns = $restrictedColumns;
        $this->_timezone = $timezone;
        $this->_user = $user;
    }
    public function getAllowedCampaignIds()
    {
        return $this->_allowedCampaignIds;
    }
    public function getRestrictedColumns()
    {
        return $this->_restrictedColumns;
    }
    public function getTimezone()
    {
        return $this->_timezone;
    }
    public function getUser()
    {
        return $this->_user;
    }
}

?>