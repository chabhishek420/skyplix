<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Branding\Model;

class Branding extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_className = "Branding";
    protected static $_tableName = "branding";
    public function getLogo()
    {
        return $this->get("logo");
    }
    public function getFavicon()
    {
        return $this->get("favicon");
    }
}

?>