<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\LpToken\Storage;

final class StorageInterface
{
    public abstract function enableCompression();
    public abstract function set($token, $value, $ttl);
    public abstract function get($token);
    public abstract function delete($token);
}

?>