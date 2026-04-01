<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\DataGetter;

final class DataGetterInterface
{
    public abstract function name();
    public abstract function get(\Traffic\CachedData\Storage\StorageInterface $storage, $scope);
    public abstract function fallback($scope);
}

?>