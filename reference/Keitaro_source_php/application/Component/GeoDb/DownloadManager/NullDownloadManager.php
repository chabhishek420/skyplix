<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\DownloadManager;

class NullDownloadManager extends DownloadManager implements DownloadManagerInterface
{
    public function delete()
    {
    }
    public function status()
    {
        return [\Component\GeoDb\GeoDbStatus::OK, ""];
    }
    public function isUpdateAvailable()
    {
        return false;
    }
    public function update()
    {
    }
}

?>