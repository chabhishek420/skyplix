<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Service;

class RawClickService extends AbstractService
{
    const INSERT_LIMIT = 1000;
    const LIMIT = 30;
    const SUBIDSEQ = "SUBIDSEQ";
    public function resolveGeo(\Traffic\RawClick $rawClick)
    {
        if ($rawClick->isGeoResolved() || $rawClick->get("country")) {
            return NULL;
        }
        $rawClick->set("is_geo_resolved", true);
        $info = \Traffic\Device\Service\IpInfoService::instance()->getIpInfo($rawClick->getIpString());
        foreach ($info as $dataType => $value) {
            $rawClick->set($dataType, $value);
        }
        if ($rawClick->getCFCountry()) {
            $rawClick->set(\Traffic\GeoDb\IpInfoType::COUNTRY, $rawClick->getCFCountry());
        }
        if ($rawClick->getOperator() && !$rawClick->getConnectionType()) {
            $rawClick->set(\Traffic\GeoDb\IpInfoType::CONNECTION_TYPE, \Component\GeoDb\Repository\ConnectionTypesRepository::CELLULAR);
        }
    }
    public function generate($visitorCode)
    {
        if (\Traffic\Redis\Service\RedisStorageService::instance()->draftStorageUsesRedis()) {
            $randomness = base_convert(\Traffic\Redis\Service\RedisStorageService::instance()->incr(SUBIDSEQ), 10, 32);
        } else {
            $randomness = uniqid();
        }
        return substr($visitorCode . $randomness, 0, LIMIT);
    }
}

?>