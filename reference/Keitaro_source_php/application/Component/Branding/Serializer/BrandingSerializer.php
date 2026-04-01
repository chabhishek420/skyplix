<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Branding\Serializer;

class BrandingSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        foreach (\Component\Branding\Model\Branding::getFields() as $key => $type) {
            if (!isset($data[$key])) {
                $data[$key] = NULL;
            }
        }
        return $data;
    }
}

?>