<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Branding\Repository;

class BrandingRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Branding\Model\Branding::definition();
    }
    public function getValues()
    {
        $branding = $this->findFirst();
        if (empty($branding)) {
            $branding = \Component\Branding\Model\Branding::build([]);
        }
        return $branding;
    }
    public function getLogo()
    {
        $branding = $this->findFirst("1");
        if (!empty($branding)) {
            return $branding->getLogo();
        }
        return NULL;
    }
    public function getFavicon()
    {
        if (!\Core\Db\DataRepository::instance()->tableExists($this->definition())) {
            return "";
        }
        $branding = $this->findFirst("1");
        if (!empty($branding)) {
            return $branding->getFavicon();
        }
        return NULL;
    }
}

?>