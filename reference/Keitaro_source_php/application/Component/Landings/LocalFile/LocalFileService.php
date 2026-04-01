<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class LocalFileService extends \Traffic\Service\AbstractService
{
    private $_landingsPath = NULL;
    const IANA_BASE64_PREFIX = "data:application/zip;base64,";
    const WIN_BASE64_PREFIX = "data:application/x-zip-compressed;base64,";
    const TMP_FOLDER = "/_tmp";
    public function setPath($path)
    {
        $this->_landingsPath = $path;
    }
    public function getStoragePath()
    {
        return $this->_landingsPath;
    }
    public function buildPath($folder)
    {
        if (!is_string($folder)) {
            throw new \Exception("'folder' must be a string");
        }
        $path = $this->getStoragePath() . "/" . $folder;
        return $path;
    }
    public function absoluteToLocalPath($absolutePath)
    {
        $localPath = str_replace(ROOT, "", $absolutePath);
        if (defined("TESTS_ROOT")) {
            $localPath = str_replace(TESTS_ROOT, "", $localPath);
        }
        $localPath = preg_replace("/(.*)\\/\\//", "\$1/", $localPath);
        return $localPath;
    }
    public function removeDirectory($systemPath)
    {
        if ($systemPath) {
            \Core\FileSystem\Service\FileSystemService::instance()->remove($systemPath);
        }
    }
    public function copyDirectory($src, $dst)
    {
        if (empty($src)) {
            throw new \Core\Application\Exception\Error("Empty src");
        }
        if ($src === $dst) {
            throw new \Core\Application\Exception\Error("src == dst");
        }
        if (!is_dir($dst)) {
            mkdir($dst, 493, true);
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $item) {
            $path = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($path)) {
                    mkdir($path);
                }
            } else {
                copy($item, $path);
            }
        }
    }
    public function replaceFiles($folder, $fileData)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("Replacing files in to '" . $folder . "'");
        if (empty($folder)) {
            throw new \Core\Application\Exception\Error("Param 'folder' is empty");
        }
        if (empty($fileData)) {
            throw new \Core\Application\Exception\Error(\Core\Locale\LocaleService::t("landings.invalid_zip"));
        }
        $systemPath = $this->buildPath($folder);
        \Core\FileSystem\Service\FileSystemService::instance()->removeContents($systemPath);
        $tmpSystemPath = $this->_unpack($systemPath, $fileData);
        $pathWithFiles = $this->findMainFolder($tmpSystemPath);
        try {
            $validator = new Validator($this->_getIndexFiles(), $this->isPhpAllowed());
            $validator->validate($pathWithFiles, $this->findIndexFile($pathWithFiles));
            $this->copyDirectory($pathWithFiles, $systemPath);
            $this->removeDirectory($tmpSystemPath);
        } catch (Validator\IncompatibleLocalFile $exception) {
            $this->removeDirectory($tmpSystemPath);
            throw $exception;
        }
    }
    public function findIndexFile($systemPath)
    {
        foreach ($this->_getIndexFiles() as $file) {
            if (file_exists($systemPath . "/" . $file)) {
                return $file;
            }
        }
        return NULL;
    }
    public function renameFolder($oldFolder, $newFolder, $pathInLanding = true)
    {
        \Traffic\Logging\Service\LoggerService::instance()->debug("Renaming " . $oldFolder . " -> " . $newFolder);
        if (empty($oldFolder)) {
            return NULL;
        }
        if ($pathInLanding) {
            $oldFolder = $this->buildPath($oldFolder);
            $newFolder = $this->buildPath($newFolder);
        }
        if (is_dir($newFolder)) {
            throw new \Core\Application\Exception\Error("A folder named " . $newFolder . " already exists");
        }
        if ($oldFolder != $newFolder && is_dir($oldFolder)) {
            rename($oldFolder, $newFolder);
        }
    }
    public function buildUrl($domain, $localPath)
    {
        if (empty($localPath)) {
            throw new \Traffic\Actions\ActionError("Empty localPath");
        }
        return join("/", ["http:/", $domain, trim($localPath, "/"), ""]);
    }
    private function _getIndexFiles()
    {
        $indexFiles = ["index.html", "index.php"];
        return $indexFiles;
    }
    public function findMainFolder($tmpPath)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iterator as $name => $file) {
            if (file_exists($file->getPath() . "/index.php") || file_exists($file->getPath() . "/index.html")) {
                return $file->getPath();
            }
        }
        return $tmpPath;
    }
    public function isPhpAllowed()
    {
        if (\Traffic\Service\ConfigService::instance()->isDemo() || !\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_ALLOW_PHP)) {
            return false;
        }
        return true;
    }
    private function _unpack($systemPath, $fileData)
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->_stringToFile($fileData)) !== true) {
            throw new \Core\Application\Exception\Error(\Core\Locale\LocaleService::t("landings.invalid_zip"));
        }
        $tmpSystemPath = $this->_getTmpPath($systemPath);
        if (!file_exists($tmpSystemPath)) {
            mkdir($tmpSystemPath, 511, true);
            if (!file_exists($tmpSystemPath)) {
                throw new \Core\Application\Exception\Error("Can't create directory " . $tmpSystemPath);
            }
        }
        $zip->extractTo($tmpSystemPath);
        $zip->close();
        return $tmpSystemPath;
    }
    private function _getTmpPath($systemPath)
    {
        return $systemPath . TMP_FOLDER;
    }
    private function _stringToFile($fileData)
    {
        $uploadDir = ini_get("upload_tmp_dir");
        if ($uploadDir == "" || is_dir($uploadDir) === false || is_writable($uploadDir) === false) {
            $uploadDir = sys_get_temp_dir();
        }
        if (is_dir($uploadDir) === false || is_writable($uploadDir) === false) {
            throw new \Core\Application\Exception\Error("Can't create temp file in directory " . $uploadDir);
        }
        $tmpFileName = tempnam($uploadDir, "lp.zip");
        $pos = strpos($fileData, ",");
        if ($pos !== false) {
            $fileData = substr($fileData, $pos + 1);
        }
        file_put_contents($tmpFileName, base64_decode($fileData), FILE_BINARY);
        if (!file_exists($tmpFileName)) {
            throw new \Core\Application\Exception\Error("Can't create temp file " . $tmpFileName);
        }
        return $tmpFileName;
    }
}

?>