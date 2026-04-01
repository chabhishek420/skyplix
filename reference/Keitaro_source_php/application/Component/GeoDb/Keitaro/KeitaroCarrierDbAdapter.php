<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Keitaro;

class KeitaroCarrierDbAdapter implements \Component\GeoDb\Adapter\GeoDbAdapterInterface
{
    private $_client = NULL;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition)
    {
        $this->_client = new DbcaClient($definition);
    }
    public function info($ip)
    {
        $code = $this->_client->rawInfo($ip);
        $info = [\Traffic\GeoDb\IpInfoType::OPERATOR => $code];
        if ($code) {
            $info[\Traffic\GeoDb\IpInfoType::CONNECTION_TYPE] = \Component\GeoDb\Repository\ConnectionTypesRepository::CELLULAR;
        }
        return $info;
    }
    public function rawInfo($ip)
    {
        return $this->_client->rawInfo($ip);
    }
    public function getDbInfo()
    {
        return $this->_client->getDbInfo();
    }
}

?>