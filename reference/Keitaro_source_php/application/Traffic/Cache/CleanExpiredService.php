<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cache;

class CleanExpiredService extends \Traffic\Service\AbstractService
{
    const EXT = ".doctrine";
    protected function _getIterator($path)
    {
        return new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
    }
    protected function _isDoctrineCache($name)
    {
        return strrpos($name, EXT) === strlen($name) - strlen(EXT);
    }
    public function cleanCacheFolder($path)
    {
        if (!is_dir($path)) {
            return NULL;
        }
        foreach ($this->_getIterator($path) as $name => $file) {
            if ($file->isDir()) {
                @rmdir($name);
            } else {
                $resource = fopen($name, "r");
                $lifetime = 1;
                if (false !== ($line = fgets($resource))) {
                    $lifetime = (int) $line;
                }
                fclose($resource);
                if ($lifetime !== 0 && $lifetime < time()) {
                    @unlink($name);
                }
            }
        }
        return true;
    }
}

?>