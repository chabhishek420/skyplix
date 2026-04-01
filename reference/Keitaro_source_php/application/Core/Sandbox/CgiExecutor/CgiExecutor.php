<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox\CgiExecutor;

class CgiExecutor implements CgiExecutorInterface
{
    private $_executableFinder = NULL;
    private $_binaryPath = NULL;
    private $_searched = false;
    public function __construct()
    {
        $this->_executableFinder = new \Symfony\Component\Process\ExecutableFinder();
    }
    public function isAvailable()
    {
        if (!function_exists("proc_open")) {
            return false;
        }
        $this->_fillInfo();
        return !empty($this->_binaryPath);
    }
    public function command()
    {
        $this->_fillInfo();
        return [$this->_binaryPath];
    }
    private function _fillInfo()
    {
        if (!$this->_searched) {
            $this->_binaryPath = $this->_findCGIBinary();
            $this->_searched = true;
        }
    }
    private function _findCGIBinary()
    {
        $dirs = ["/usr/bin/"];
        if ($php = getenv("PHP_BINARY")) {
            $dirs[] = dirname($php);
        }
        $dirs[] = PHP_BINDIR;
        $fileNames = ["php" . PHP_MAJOR_VERSION . PHP_MINOR_VERSION . "-cgi", "php-cgi"];
        foreach ($dirs as $dir) {
            foreach ($fileNames as $fileName) {
                $path = $dir . $fileName;
                if (is_executable($path)) {
                    return $path;
                }
            }
        }
        $dirs = [PHP_BINDIR];
        return $this->_executableFinder->find("php-cgi", false, $dirs);
    }
}

?>