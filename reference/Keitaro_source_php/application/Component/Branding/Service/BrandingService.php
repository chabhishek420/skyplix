<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Branding\Service;

class BrandingService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Branding\Model\Branding::definition();
    }
    public function updateValues($brandingSettings)
    {
        $branding = \Component\Branding\Repository\BrandingRepository::instance()->findFirst();
        if (empty($branding)) {
            $branding = new \Component\Branding\Model\Branding();
        }
        $branding->setData($brandingSettings);
        if ($branding->getId()) {
            $this->save($branding);
        } else {
            $this->create($brandingSettings);
        }
        return $branding;
    }
}

?>