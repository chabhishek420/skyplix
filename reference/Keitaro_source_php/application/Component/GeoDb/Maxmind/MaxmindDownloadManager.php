<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Maxmind;

class MaxmindDownloadManager extends \Component\GeoDb\DownloadManager\DownloadManager implements \Component\GeoDb\DownloadManager\DownloadManagerInterface
{
    const SOURCE_URL = "https://download.maxmind.com/app/geoip_download?edition_id=%edition%&suffix=%suffix%&license_key=%key%";
    const FILE_SUFFIX = "tar.gz";
    const MD5_SUFFIX = "tar.gz.md5";
    const KEY = "key";
    const EDITION = "edition";
    public function isUpdateAvailable()
    {
        if (!$this->_hasKey()) {
            return false;
        }
        if (!file_exists($this->getFilePath()) || filesize($this->getFilePath()) == 0) {
            return false;
        }
        if (file_exists($this->getFilePathAsArchive())) {
            return $this->getRemoteMd5() != md5(file_get_contents($this->getFilePathAsArchive()));
        }
        return true;
    }
    public function _download($remoteFile, $localFile)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("Request: " . $remoteFile);
        if (!file_exists($this->getFilePathAsArchive()) || $this->getRemoteMd5() != md5(file_get_contents($this->getFilePathAsArchive()))) {
            try {
                \Traffic\Http\Service\HttpService::instance()->download($remoteFile, $localFile, [], [\GuzzleHttp\RequestOptions::TIMEOUT => 600]);
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] Download error with status " . $e->getCode());
            }
        }
    }
    public function delete()
    {
        if (is_file($this->getFilePath())) {
            unlink($this->getFilePath());
        }
    }
    private function _hasKey()
    {
        return $this->hasOption(KEY);
    }
    public function status()
    {
        if (!$this->_hasKey()) {
            return [\Component\GeoDb\GeoDbStatus::NO_KEY, ""];
        }
        if (!$this->_isKeyValid()) {
            return [\Component\GeoDb\GeoDbStatus::ERROR, \Core\Locale\LocaleService::instance()->t("geo_dbs.incorrect_key")];
        }
        return [\Component\GeoDb\GeoDbStatus::OK];
    }
    private function _isKeyValid()
    {
        $response = $this->getRemoteFileInfoResponse();
        return $response && $response->getHeaderLine("Content-Disposition");
    }
    public function getPathOnly()
    {
        return $this->definition()->filePath();
    }
    public function getFilePath()
    {
        return $this->getPathOnly();
    }
    public function _getUnpackedFilePath()
    {
        return $this->_findFile();
    }
    public function update()
    {
        if (!$this->_hasKey()) {
            return false;
        }
        $this->_download($this->getRemoteUrl(FILE_SUFFIX), $this->getFilePathAsArchive());
        $this->_unpack($this->getFilePathAsArchive());
        $this->_rename($this->_getUnpackedFilePath(), $this->getFilePath());
    }
    private function getRemoteFileInfoResponse()
    {
        while (!isset($this->_cachedResponse)) {
            try {
                $response = \Traffic\Http\Service\HttpService::instance()->get($this->getRemoteUrl(FILE_SUFFIX));
                $code = $response->getStatusCode();
                if (400 <= $code) {
                    return NULL;
                }
                $this->_cachedResponse = $response;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                return NULL;
            }
        }
        return $this->_cachedResponse;
    }
    private function _getKey()
    {
        return $this->option(KEY);
    }
    public function getFilePathAsArchive()
    {
        return $this->getFilePath() . ".tar.gz";
    }
    private function _unpack($archive)
    {
        while (!class_exists("PharData")) {
            $phar = new \PharData($archive);
            try {
                $phar->extractTo(dirname($archive), NULL, true);
            } catch (\Exception $e) {
                throw new \Component\GeoDb\Error\DbUpdateError($e->getMessage());
            }
        }
        throw new \Component\GeoDb\Error\DbUpdateError("Unable to unpack (class PharData not exists)");
    }
    public function getRemoteMd5()
    {
        $path = $this->getRemoteUrl(MD5_SUFFIX);
        try {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Request: " . $path);
            $md5 = \Traffic\Http\Service\HttpService::instance()->get($path)->getBody();
            \Traffic\Logging\Service\LoggerService::instance()->debug("Response:" . $md5);
            return $md5;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }
    public function getRemoteUrl($suffix)
    {
        $url = str_replace("%suffix%", $suffix, SOURCE_URL);
        $url = str_replace("%edition%", $this->_getEdition(), $url);
        $url = str_replace("%key%", $this->_getKey(), $url);
        return $url;
    }
    private function _getEdition()
    {
        return $this->option(EDITION);
    }
    private function _rename($unpackedFilePath, $dbPath)
    {
        $localMd5 = md5(file_get_contents($this->getFilePathAsArchive()));
        $remoteMd5 = $this->getRemoteMd5();
        if ($remoteMd5 != $localMd5) {
            throw new \Component\GeoDb\Error\DbError("md5 is incorrect (" . $localMd5 . "<>" . $remoteMd5 . ")");
        }
        rename($unpackedFilePath, $dbPath);
    }
    private function _findFile()
    {
        $dirName = dirname($this->getFilePath());
        $it = new \RecursiveDirectoryIterator($dirName);
        foreach (new \RecursiveIteratorIterator($it) as $file) {
            if (preg_match("/.+\\.dat\$/i", $file) && !strstr($file, ".tar") && $file != $this->getFilePath()) {
                return $file;
            }
        }
        throw new \Component\GeoDb\Error\DbError("Can't find file in #" . $dirName);
    }
}

?>