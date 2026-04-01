<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

class GetDomains implements DataGetterInterface
{
    const NAME = "domains";
    const CACHE_KEY = "DOMAINS";
    public function name()
    {
        return NAME;
    }
    public function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope = NULL)
    {
        if (!empty($scope)) {
            throw new \Exception("scope is not implemented");
        }
        $result = $storage->get(CACHE_KEY);
        $domains = [];
        foreach ($result as $data) {
            $domains[] = \Component\Domains\Service\DomainService::instance()->restore($data);
        }
        return $domains;
    }
    public function fallback($scope = NULL)
    {
        return \Component\Domains\Repository\DomainsRepository::instance()->all();
    }
}

?>