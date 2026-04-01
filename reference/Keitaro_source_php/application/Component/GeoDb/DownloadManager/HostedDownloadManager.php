<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\DownloadManager;

class HostedDownloadManager extends DownloadManager implements DownloadManagerInterface
{
    const DB_URL = "db_url";
    const CRC_URL = "crc_url";
    public function delete()
    {
        if (is_file($this->_getFilePathAsArchive())) {
            unlink($this->_getFilePathAsArchive());
        }
        if (is_file($this->getFilePath())) {
            unlink($this->getFilePath());
        }
        if (is_file($this->_getFileCrcPath())) {
            unlink($this->_getFileCrcPath());
        }
    }
    public function status()
    {
        return [\Component\GeoDb\GeoDbStatus::OK, ""];
    }
    public function isUpdateAvailable()
    {
        try {
            $remoteCrc32 = \Traffic\Http\Service\HttpService::instance()->get($this->_getRemoteCrcUrl())->getBody();
            $localCrc32 = $this->_getLocalCrc32();
            return $remoteCrc32 != $localCrc32;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] Error while request db, status: " . $e->getCode());
        }
    }
    public function update()
    {
        $localArchive = $this->_getFilePathAsArchive();
        $this->_download($this->_getSourceUrl(), $localArchive);
        $this->_unpack($this->_getFilePathAsArchive(), $this->_getUnpackedFilePath());
        $this->_rename($this->_getUnpackedFilePath(), $this->getFilePath());
    }
    private function _getFilePathAsArchive()
    {
        return $this->getFilePath() . ".gz";
    }
    private function _getSourceUrl()
    {
        return $this->option(DB_URL);
    }
    private function _getRemoteCrcUrl()
    {
        return $this->option(CRC_URL);
    }
    protected function _download($remoteUrl, $localPath)
    {
        $dir = dirname($localPath);
        if (!file_exists($dir)) {
            mkdir($dir, 511, true);
        }
        if (file_exists($localPath)) {
            unlink($localPath);
        }
        try {
            \Traffic\Http\Service\HttpService::instance()->download($remoteUrl, $localPath, [], [\GuzzleHttp\RequestOptions::TIMEOUT => 600]);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] Error while request db, status: " . $e->getCode());
        }
    }
    private function _unpack($archive, $file)
    {
        $sfp = gzopen($archive, "rb");
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 511, true);
        }
        if (!is_writable($dir)) {
            throw new \Component\GeoDb\Error\DbUpdateError("Directory " . $dir . " is not writable");
        }
        if (file_exists($file)) {
            unlink($file);
        }
        $fp = fopen($file, "w");
        while ($string = gzread($sfp, 4096)) {
            fwrite($fp, $string, strlen($string));
        }
        gzclose($sfp);
        fclose($fp);
        unlink($archive);
    }
    private function _getUnpackedFilePath()
    {
        $archive = $this->_getFilePathAsArchive();
        return $archive . ".part";
    }
    private function _rename($unpackedFilePath, $dbPath)
    {
        $crc32 = sprintf("%u", \Traffic\Http\Service\HttpService::instance()->get($this->_getRemoteCrcUrl())->getBody()->getContents());
        $downloadedFileCrc = $this->_calculateFileCrc($unpackedFilePath);
        if ((int) $downloadedFileCrc === 0) {
            throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] CRC32 of downloaded file has zero checksum (" . $crc32 . "/" . $downloadedFileCrc . ")");
        }
        if ($crc32 != $downloadedFileCrc) {
            throw new \Component\GeoDb\Error\DbUpdateError("[" . $this->definition()->id() . "] CRC32 of downloaded file doesn't match (" . $crc32 . "/" . $downloadedFileCrc . ")");
        }
        rename($unpackedFilePath, $dbPath);
        file_put_contents($dbPath . ".crc", $crc32);
    }
    private function _calculateFileCrc($filePath)
    {
        if (file_exists($filePath)) {
            $fileString = file_get_contents($filePath);
            $crc = crc32($fileString);
            return sprintf("%u", $crc);
        }
        return NULL;
    }
    private function _getLocalCrc32()
    {
        if (!file_exists($this->getFilePath())) {
            return "0";
        }
        $crcFile = $this->getFilePath() . ".crc";
        if (file_exists($crcFile)) {
            return file_get_contents($crcFile);
        }
        return NULL;
    }
    private function _getFileCrcPath()
    {
        return $this->getFilePath() . ".crc";
    }
}

?>