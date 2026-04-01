<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Model;

class ThirdPartyIntegration extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_className = "ThirdPartyIntegration";
    protected static $_tableName = "third_party_integration";
    public function getIntegrationParams()
    {
        return $this->get("settings");
    }
    public function getIntegrationName()
    {
        $data = $this->get("settings");
        return $data["name"];
    }
}

?>