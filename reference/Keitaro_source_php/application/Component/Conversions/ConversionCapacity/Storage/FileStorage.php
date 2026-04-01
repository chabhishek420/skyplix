<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\ConversionCapacity\Storage;

class FileStorage implements \Component\Conversions\ConversionCapacity\StorageInterface
{
    private $_path = NULL;
    const TTL = 2;
    const TMP_FILE = "_tmp";
    public function __construct($path = false)
    {
        $this->_path = $path;
    }
    public function store(\Traffic\Model\Offer $offer, \Traffic\Model\Conversion $conversion)
    {
        $filePath = $this->_getFileName($offer->getId());
        file_put_contents($filePath, $conversion->getPostbackDatetime()->getTimestamp() . "\n", FILE_APPEND);
    }
    public function currentValueForOffer(\Traffic\Model\Offer $offer, \DateTime $currentDateTime)
    {
        $filePath = $this->_getFileName($offer->getId());
        if (!file_exists($filePath)) {
            return 0;
        }
        $from = $currentDateTime->setTime(0, 0, 0)->getTimestamp();
        $handle = @fopen($filePath, "r");
        if (!$handle) {
            throw new \Core\Application\Exception\Error("Can't read file " . $filePath);
        }
        $cnt = 0;
        while (($buffer = fgets($handle, 4096)) !== false) {
            $buffer = trim($buffer);
            if ($from <= trim($buffer)) {
                $cnt++;
            }
        }
        if (!feof($handle)) {
            throw new \Core\Application\Exception\Error("Can't close file " . $filePath);
        }
        fclose($handle);
        return $cnt;
    }
    public function totalValueForOffer(\Traffic\Model\Offer $offer)
    {
        return $this->currentValueForOffer($offer, new \DateTime("2015-01-01"));
    }
    public function prune(\DateTime $currentDateTime)
    {
        $time = clone $currentDateTime;
        $until = $time->modify("-" . TTL . " days")->getTimestamp();
        if (!is_dir($this->_path)) {
            return NULL;
        }
        foreach (new \DirectoryIterator($this->_path) as $fileInfo) {
            if (!($fileInfo->isDot() || strstr($fileInfo->getFilename(), TMP_FILE))) {
                $this->_pruneFileContent($fileInfo->getFilename(), $until);
            }
        }
    }
    private function _pruneFileContent($fileName, $until)
    {
        $filePath = $this->_path . "/" . $fileName;
        $tmpFilePath = $filePath . TMP_FILE;
        $file = fopen($filePath, "r");
        $tmpFile = fopen($tmpFilePath, "a+");
        $cnt = 0;
        while (($buffer = fgets($file, 4096)) !== false) {
            if ($until <= $buffer) {
                $cnt++;
                fwrite($tmpFile, $buffer);
            }
        }
        fclose($file);
        fclose($tmpFile);
        if ($cnt == 0) {
            unlink($tmpFilePath);
            unlink($filePath);
        } else {
            rename($tmpFilePath, $filePath);
        }
    }
    private function _getFileName($offerId)
    {
        if (!is_dir($this->_path)) {
            mkdir($this->_path, 511, true);
        }
        $filePath = $this->_path . "/offer_" . $offerId;
        return $filePath;
    }
}

?>