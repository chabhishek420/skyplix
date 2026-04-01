<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CachedData\Storage;

final class StorageInterface
{
    public abstract function set($key, $data);
    public abstract function get($key);
    public abstract function delete($key);
    public abstract function deleteAll();
    public abstract function exists($key);
    public abstract function size();
    public abstract function info();
    public abstract function commit();
}

?>