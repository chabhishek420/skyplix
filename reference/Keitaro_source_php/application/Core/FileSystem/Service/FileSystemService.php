<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\FileSystem\Service;

class FileSystemService extends \Traffic\Service\AbstractService
{
    private $_adapter = NULL;
    public function getAdapter()
    {
        return $this->_adapter;
    }
    public static function defaultAdapter()
    {
        return new \Symfony\Component\Filesystem\Filesystem();
    }
    public function setAdapter($fileSystem)
    {
        $this->_adapter = $fileSystem;
    }
    public function remove($files)
    {
        $this->getAdapter()->remove($files);
    }
    public function removeContents($path)
    {
        if (!is_dir($path)) {
            return NULL;
        }
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $name => $file) {
            if ($file->isDir()) {
                rmdir($name);
            } else {
                unlink($name);
            }
        }
    }
    public function mkdir($dirs, $mode = 511)
    {
        $this->getAdapter()->mkdir($dirs, $mode);
    }
    public function mirror($originDir, $targetDir, \Traversable $iterator = NULL, $options = [])
    {
        $this->getAdapter()->mirror($originDir, $targetDir, $iterator, $options);
    }
    public function copy($originFile, $targetFile, $overwriteNewerFiles = false)
    {
        $this->getAdapter()->copy($originFile, $targetFile, $overwriteNewerFiles);
    }
    public function rename($origin, $target, $overwrite = false)
    {
        $this->getAdapter()->rename($origin, $target, $overwrite);
    }
    public function chmod($files, $mode, $umask = 0, $recursive = false)
    {
        $this->getAdapter()->chmod($files, $mode, $umask, $recursive);
    }
    public function exists($files)
    {
        return $this->getAdapter()->exists($files);
    }
    public function dumpFile($filename, $content)
    {
        return $this->getAdapter()->dumpFile($filename, $content);
    }
    public function touch($files, $time = NULL, $atime = NULL)
    {
        return $this->getAdapter()->touch($files, $time, $atime);
    }
    public function getGlobFiles($fullPathPattern, $onlyFiles = false)
    {
        $iter = new \GlobIterator($fullPathPattern, \GlobIterator::SKIP_DOTS);
        foreach ($iter as $file) {
            if (!($onlyFiles && $file->isDir())) {
            }
        }
    }
}

?>