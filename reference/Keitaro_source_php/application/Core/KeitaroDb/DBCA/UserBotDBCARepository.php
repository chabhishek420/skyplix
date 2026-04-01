<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\KeitaroDb\DBCA;

class UserBotDBCARepository extends \Traffic\Repository\AbstractBaseRepository
{
    public function getFullPath($version)
    {
        return ROOT . "/var/bots/user_bot" . $version . ".dbca";
    }
    public function getCurrentVersion()
    {
        $version = "0";
        $versionPath = $this->getVersionPath();
        if (is_file($versionPath)) {
            $version = (int) file_get_contents($versionPath);
            if (empty($version)) {
                $version = "0";
            }
        }
        return $version;
    }
    public function getVersionPath()
    {
        return ROOT . "/var/bots/user_bot.dbca.version";
    }
    public function getDbStream($readOnly, $version)
    {
        $path = $this->getFullPath($version);
        if (!is_file($path) && $readOnly) {
            return NULL;
        }
        $dbStream = new \Core\KeitaroDb\Common\DbStream($path, $readOnly);
        return $dbStream;
    }
    public function getCurrentDbPath()
    {
        $version = $this->getCurrentVersion();
        return $this->getFullPath($version);
    }
    public function getOldDbs($excludeNLatest = 0)
    {
        $directory = new \DirectoryIterator(ROOT . "/var/bots/");
        $regexp = "#^user_bot\\d+.dbca\$#";
        $files = new \RegexIterator($directory, $regexp);
        $usedDb = basename($this->getCurrentDbPath());
        $result = [];
        foreach ($files as $f) {
            if ($f->isFile() && $f->getFilename() != $usedDb) {
                $result[$f->getMTime()][] = $f->getRealPath();
            }
        }
        ksort($result);
        for ($i = 0; $i < $excludeNLatest; $i++) {
            array_pop($result);
        }
        $resultNormalized = [];
        foreach ($result as $files) {
            $resultNormalized = array_merge($resultNormalized, $files);
        }
        return array_values($resultNormalized);
    }
}

?>