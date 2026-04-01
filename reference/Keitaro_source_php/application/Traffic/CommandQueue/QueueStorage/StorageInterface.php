<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\CommandQueue\QueueStorage;

final class StorageInterface
{
    public abstract function push($command);
    public abstract function pop();
    public abstract function count();
    public abstract function isAvailable();
    public abstract function clean();
    public abstract function enableCompression();
}

?>