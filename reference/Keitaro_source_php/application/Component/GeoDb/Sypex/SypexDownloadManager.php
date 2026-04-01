<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Sypex;

class SypexDownloadManager extends \Component\GeoDb\DownloadManager\DownloadManager implements \Component\GeoDb\DownloadManager\DownloadManagerInterface
{
    protected $_url = NULL;
    const KEY = "key";
    const DB_FILE = "db_name";
    const UNPACKED_DB_FILE = "unpacked_db_file";
    public function isUpdateAvailable()
    {
        if (!$this->_hasKey()) {
            return false;
        }
        return $this->timestamp() < $this->_getRemoteVersion();
    }
    public function getSourceUrl()
    {
        $url = str_replace("%" . KEY . "%", $this->_getKey(), $this->_url);
        $url = str_replace("%" . DB_FILE . "%", $this->_getDbName(), $url);
        return $url;
    }
    public function update()
    {
        if (!$this->_hasKey()) {
            return NULL;
        }
        $url = $this->getSourceUrl();
        $this->_download($url, $this->_getFilePathAsArchive());
        $this->_unpack($this->_getFilePathAsArchive());
        $this->_rename($this->_getUnpackedFilePath(), $this->getFilePath());
    }
    public function delete()
    {
        if (is_file($this->getFilePath())) {
            unlink($this->getFilePath());
        }
        if (is_file($this->_getFilePathAsArchive())) {
            unlink($this->_getFilePathAsArchive());
        }
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
    private function _download($remoteFile, $localFile)
    {
        try {
            \Traffic\Logging\Service\LoggerService::instance()->debug("Request: " . $remoteFile);
            \Traffic\Http\Service\HttpService::instance()->download($remoteFile, $localFile, [], [\GuzzleHttp\RequestOptions::TIMEOUT => 600]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] Download error with status " . $e->getCode());
        }
    }
    private function _rename($unpackedFilePath, $dbPath)
    {
        if (!file_exists($unpackedFilePath)) {
            throw new \Component\GeoDb\Error\DbError("Archive doesn't contain file " . $unpackedFilePath);
        }
        if (file_exists($unpackedFilePath) && $unpackedFilePath != $dbPath) {
            rename($unpackedFilePath, $dbPath);
        }
    }
    private function _isKeyValid()
    {
        $response = $this->_getRemoteFileInfoResponse();
        return $response && $response->getHeaderLine("Content-Disposition");
    }
    private function _getFilePathAsArchive()
    {
        return $this->definition()->filePath() . ".zip";
    }
    private function _getKey()
    {
        return $this->option(KEY);
    }
    private function _hasKey()
    {
        return $this->hasOption(KEY);
    }
    private function _unpack($archive)
    {
        $zip = new \ZipArchive();
        $res = $zip->open($archive);
        if ($res === true) {
            $dir = dirname($archive);
            $zip->extractTo($dir);
            $zip->close();
            unlink($archive);
        } else {
            throw new \Component\GeoDb\Error\DbError("Can't unpack " . $archive . " (" . $zip->getStatusString() . ")");
        }
    }
    private function _getRemoteVersion()
    {
        $response = $this->_getRemoteFileInfoResponse();
        if ($response) {
            $header = $response->getHeaderLine("Last-Modified");
            return new \DateTime($header);
        }
        return NULL;
    }
    private function _getDbName()
    {
        return $this->option(DB_FILE);
    }
    private function _getUnpackedFilePath()
    {
        return dirname($this->getFilePath()) . "/" . $this->option(UNPACKED_DB_FILE);
    }
    private function _getRemoteFileInfoResponse()
    {
        try {
            $response = \Traffic\Http\Service\HttpService::instance()->get($this->getSourceUrl());
            $code = $response->getStatusCode();
            if (400 <= $code) {
                return NULL;
            }
            return $response;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }
}

?>