<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class VisitorService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Clicks\Model\Visitor::definition();
    }
    public function generateCode(\Traffic\RawClick $rawClick)
    {
        $srcString = $rawClick->getIpString();
        $srcString .= $rawClick->getUserAgent();
        $srcString .= $rawClick->getConnectionType();
        $srcString .= $rawClick->getCountry();
        $srcString .= $rawClick->getCity();
        $srcString .= $rawClick->getDeviceModel();
        return murmurhash3($srcString);
    }
}

?>