<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class Validator
{
    private $_allowedIndexFiles = NULL;
    private $_phpAllowed = NULL;
    public function __construct($allowedIndexFiles, $phpAllowed)
    {
        $this->_allowedIndexFiles = $allowedIndexFiles;
        $this->_phpAllowed = $phpAllowed;
    }
    private function _forbiddenFiles()
    {
        return ["kclient.php", "kclick_client.php"];
    }
    private function _forbiddenExtensions()
    {
        return ["sh"];
    }
    private function _executablePhpExtensions()
    {
        return ["php", "phtml", "php5", "php4"];
    }
    private function _forbiddenFunctions()
    {
        return ["system()" => "system(", "exec()" => "/^\\.exec\\(/"];
    }
    public function validate($systemPath, $indexFile)
    {
        $foundFiles = $this->containsForbiddenFiles($systemPath);
        if (!empty($foundFiles)) {
            $error = \Core\Locale\LocaleService::t("landings.forbidden_files", [implode(", ", $foundFiles)]);
            throw new Validator\IncompatibleLocalFile($error);
        }
        if (empty($indexFile)) {
            $error = \Core\Locale\LocaleService::t("landings.no_index_in_zip", [implode(", ", $this->_allowedIndexFiles)]);
            throw new Validator\IncompatibleLocalFile($error);
        }
        if ($functionName = $this->containsForbiddenFunction($systemPath, $indexFile)) {
            $functionPattern = $this->_forbiddenFunctions()[$functionName];
            $lineNumber = $this->_searchLineNumber($systemPath, $indexFile, $functionPattern);
            $error = \Core\Locale\LocaleService::t("landings.forbidden_function", [$functionName, $lineNumber]);
            throw new Validator\IncompatibleLocalFile($error);
        }
        $this->containsForbiddenCharset($systemPath, $indexFile);
    }
    public function containsForbiddenFiles($systemPath)
    {
        $list = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($systemPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        $forbiddenExtensions = $this->_forbiddenExtensions();
        if (!$this->_phpAllowed) {
            $forbiddenExtensions = array_merge($forbiddenExtensions, $this->_executablePhpExtensions());
        }
        $blacklist = $this->_forbiddenFiles();
        foreach ($iterator as $name => $file) {
            if (in_array($file->getFilename(), $blacklist) || in_array($file->getExtension(), $forbiddenExtensions)) {
                $list[] = $this->_getFilePath($file, $systemPath);
            }
        }
        return $list;
    }
    public function containsForbiddenFunction($systemPath, $indexFile)
    {
        $content = file_get_contents($systemPath . "/" . $indexFile);
        foreach ($this->_forbiddenFunctions() as $functionName => $functionPattern) {
            if (substr($functionPattern, 0, 1) == "/") {
                if (preg_match($functionPattern, $content)) {
                    return $functionName;
                }
            } else {
                if (strstr($content, $functionPattern)) {
                    return $functionName;
                }
            }
        }
        return false;
    }
    public function containsForbiddenCharset($systemPath, $indexFile)
    {
        $content = file_get_contents($systemPath . "/" . $indexFile);
        if (preg_match("/<meta[^>]+charset[^>]{1,5}(?:win|cp)[^>]{0,5}1251/mi", $content)) {
            $error = \Core\Locale\LocaleService::t("landings.forbidden_charset", ["windows-1251"]);
            throw new Validator\IncompatibleLocalFile($error);
        }
        return false;
    }
    private function _searchLineNumber($systemPath, $indexFile, $function)
    {
        $path = $systemPath . "/" . $indexFile;
        $lineNumber = 0;
        if ($handle = fopen($path, "r")) {
            $count = 0;
            while (($line = fgets($handle, 4096)) !== false && !$lineNumber) {
                $count++;
                $lineNumber = strpos($line, $function) !== false ? $count : $lineNumber;
            }
            fclose($handle);
        }
        return $lineNumber;
    }
    private function _getFilePath($file, $systemPath)
    {
        $start = strlen($systemPath);
        return trim(substr($file->getPathname(), $start), "/");
    }
}

?>