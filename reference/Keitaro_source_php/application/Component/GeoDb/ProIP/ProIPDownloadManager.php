<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\ProIP;

class ProIPDownloadManager extends \Component\GeoDb\DownloadManager\DownloadManager implements \Component\GeoDb\DownloadManager\DownloadManagerInterface
{
    const TOKEN = "token";
    const DOWNLOAD_TIMEOUT = 300;
    const DOWNLOAD_FILENAME = "output";
    const API_URL = "https://download.proip.info/database/%token%";
    const INFO_URL = "https://download.proip.info/status/%token%";
    public function status()
    {
        if (!$this->_hasToken()) {
            return [\Component\GeoDb\GeoDbStatus::NO_KEY, ""];
        }
        $body = $this->_getRemoteFileInfoResponse()->getBody();
        $data = json_decode($body);
        if (empty($data->status)) {
            return [\Component\GeoDb\GeoDbStatus::ERROR, \Core\Locale\LocaleService::instance()->t("geo_dbs.errors.empty_reply")];
        }
        if ($data->status == "disable") {
            return [\Component\GeoDb\GeoDbStatus::ERROR, \Core\Locale\LocaleService::instance()->t("geo_dbs.errors.subscription_expired")];
        }
        if ($data->status == "active") {
            return [\Component\GeoDb\GeoDbStatus::OK];
        }
        return [\Component\GeoDb\GeoDbStatus::ERROR, \Core\Locale\LocaleService::instance()->t("geo_dbs.errors.unknown_error") . ": " . $body];
    }
    public function isUpdateAvailable()
    {
        while (!$this->_hasToken()) {
            try {
                $remoteVersion = $this->_getRemoteVersion();
                $localVersion = $this->_getLocalVersion();
                if ($remoteVersion && !file_exists($this->getFilePath())) {
                    return true;
                }
                return $remoteVersion != $localVersion;
            } catch (\Core\Application\Exception\Error $e) {
                return false;
            }
        }
        return false;
    }
    public function update()
    {
        $dir = dirname($this->getFilePath());
        if (!is_dir($dir)) {
            mkdir($dir, 511, true);
        }
        $remoteVersion = $this->_getRemoteVersion();
        $this->_download($this->getApiUrl(), $this->_getFilePathAsArchive());
        $this->_unpack($this->_getFilePathAsArchive());
        $this->_setLocalVersion($remoteVersion);
    }
    public function getInfoUrl()
    {
        return $this->_substUrl(INFO_URL);
    }
    public function getApiUrl()
    {
        return $this->_substUrl(API_URL);
    }
    private function _getRemoteFileInfoResponse()
    {
        return \Traffic\Http\Service\HttpService::instance()->get($this->getInfoUrl());
    }
    private function _getToken()
    {
        return $this->option(TOKEN);
    }
    public function delete()
    {
        if (file_exists($this->getFilePath())) {
            unlink($this->getFilePath());
        }
        if (file_exists($this->_getFilePathAsArchive())) {
            unlink($this->_getFilePathAsArchive());
        }
        if (file_exists($this->_getLocalVersionPath())) {
            unlink($this->_getLocalVersionPath());
        }
    }
    private function _getRemoteVersion()
    {
        $body = $this->_getRemoteFileInfoResponse()->getBody()->getContents();
        $data = json_decode($body);
        if (isset($data->version)) {
            return $data->version;
        }
        throw new \Component\GeoDb\Error\DbError("Status " . $data->status);
    }
    private function _getLocalVersionPath()
    {
        return dirname($this->getFilePath()) . "/version.txt";
    }
    private function _getLocalVersion()
    {
        $localVersionPath = $this->_getLocalVersionPath();
        if (!is_file($localVersionPath)) {
            return "-1";
        }
        return file_get_contents($localVersionPath);
    }
    private function _setLocalVersion($version)
    {
        file_put_contents($this->_getLocalVersionPath(), $version);
    }
    private function _getFilePathAsArchive()
    {
        return $this->getFilePath() . ".zip";
    }
    private function _hasToken()
    {
        return $this->hasOption(TOKEN);
    }
    private function _download($remoteFile, $localFile)
    {
        if (file_exists($localFile)) {
            unlink($localFile);
        }
        \Traffic\Logging\Service\LoggerService::instance()->debug("Download: " . $remoteFile);
        \Traffic\Http\Service\HttpService::instance()->download($remoteFile, $localFile, [], ["timeout" => DOWNLOAD_TIMEOUT]);
    }
    private function _unpack($archivePath)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($archivePath);
        if ($res === true) {
            $archiveDir = dirname($archivePath);
            $unpackDir = $archiveDir;
            $unpackDir .= "/tmp";
            if (!is_dir($unpackDir)) {
                mkdir($unpackDir, 511, true);
            }
            $zip->extractTo($unpackDir);
            $zip->close();
            unlink($archivePath);
            \Traffic\Tools\Tools::moveFilesFromDirectory($unpackDir, $archiveDir);
            \Core\FileSystem\Service\FileSystemService::instance()->remove($unpackDir);
            \Core\FileSystem\Service\FileSystemService::instance()->rename($archiveDir . "/" . DOWNLOAD_FILENAME, $archiveDir . "/" . $this->option("databaseFileName"), true);
        } else {
            if (filesize($archivePath) < 500) {
                $content = file_get_contents($archivePath);
                throw new \Component\GeoDb\Error\DbUpdateError("Error while downloading '" . $content . "'");
            }
            throw new \Component\GeoDb\Error\DbUpdateError("Can't unpack " . $archivePath . " (" . $zip->getStatusString() . ")");
        }
    }
    private function _substUrl($url)
    {
        return str_replace("%token%", $this->_getToken(), $url);
    }
}

?>