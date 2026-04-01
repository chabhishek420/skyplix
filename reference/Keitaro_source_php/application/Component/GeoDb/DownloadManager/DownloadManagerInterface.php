<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\DownloadManager;

final class DownloadManagerInterface
{
    public abstract function __construct(\Component\GeoDb\GeoDbDefinition $definition, $key);
    public abstract function update();
    public abstract function delete();
    public abstract function status();
    public abstract function timestamp();
    public abstract function isUpdateAvailable();
    public abstract function option($name);
}

?>