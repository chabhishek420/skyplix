<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Branding\Controller;

class BrandingController extends \Admin\Controller\BaseController
{
    protected function _checkBusiness()
    {
        if (!\Core\Application\FeatureService::instance()->hasBrandingFeature()) {
            throw new \Core\Application\Exception\EditionError();
        }
    }
    public function indexAction()
    {
        $this->_checkBusiness();
        $brandingSettings = \Component\Branding\Repository\BrandingRepository::instance()->getValues();
        return $this->serialize($brandingSettings, new \Component\Branding\Serializer\BrandingSerializer());
    }
    public function updateAction()
    {
        if (!$this->isAdmin()) {
            $this->throwDeny();
        }
        if (\Traffic\Service\ConfigService::instance()->isDemo()) {
            $this->throwDenyBecauseDemo();
        }
        $this->_checkBusiness();
        if (!$this->isPost()) {
            return NULL;
        }
        \Component\Branding\Service\BrandingService::instance()->updateValues($this->getPostParams());
        $brandingSettings = \Component\Branding\Repository\BrandingRepository::instance()->getValues();
        return $this->serialize($brandingSettings, new \Component\Branding\Serializer\BrandingSerializer());
    }
}

?>