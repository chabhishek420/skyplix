<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\Serializer;

class ThirdPartyIntegrationSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        foreach (\Component\ThirdPartyIntegration\Model\ThirdPartyIntegration::getFields() as $key => $type) {
            if (!isset($data[$key])) {
                $data[$key] = NULL;
            }
        }
        $result = $data["settings"];
        $result["id"] = $data["id"];
        return $result;
    }
}

?>