<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Cache;

class FlushOldNamespacedCacheService extends \Traffic\Service\AbstractService
{
    private $_doctrineCache = NULL;
    private $_namespaceHash = NULL;
    public function init(\Doctrine\Common\Cache\CacheProvider $doctrineCache, $namespaceHash)
    {
        $this->_doctrineCache = $doctrineCache;
        $this->_namespaceHash = $namespaceHash;
    }
    public function flush()
    {
        if (is_null($this->_doctrineCache) || is_null($this->_namespaceHash)) {
            throw new \Exception("Call init() method first");
        }
        get_class($this->_doctrineCache);
        switch (get_class($this->_doctrineCache)) {
            case "Doctrine\\Common\\Cache\\FilesystemCache":
                return $this->_flushOldNamespacedFilesystemCache();
                break;
            case "Doctrine\\Common\\Cache\\RedisCache":
                return $this->_flushOldNamespacedRedisCache();
                break;
            default:
                return false;
        }
    }
    private function _flushOldNamespacedFilesystemCache()
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_doctrineCache->getDirectory(), \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST);
        $namespaceCacheKeyStart = substr(\Doctrine\Common\Cache\CacheProvider::DOCTRINE_NAMESPACE_CACHEKEY, 0, -4);
        $namespaceCacheKeyStartEncoded = bin2hex(substr(\Doctrine\Common\Cache\CacheProvider::DOCTRINE_NAMESPACE_CACHEKEY, 0, -4));
        $namespacesVersions = [];
        $filenamesByNamespaceVersions = [];
        $fsService = \Core\FileSystem\Service\FileSystemService::instance();
        foreach ($iterator as $name => $file) {
            if (!($file->isDir() || strpos($name, \Doctrine\Common\Cache\FilesystemCache::EXTENSION) === false || strpos($name, "_") === 0)) {
                if (!strstr($name, "unison")) {
                    $cacheFileBasename = basename(substr($name, 0, strpos($name, \Doctrine\Common\Cache\FilesystemCache::EXTENSION)));
                    $decodedFilename = hex2bin($cacheFileBasename);
                    if ($decodedFilename !== false) {
                        if (strpos($cacheFileBasename, $namespaceCacheKeyStartEncoded) === 0) {
                            if (preg_match("/" . $namespaceCacheKeyStart . "\\[(?<namespace>\\w+)\\]/", $decodedFilename, $matches)) {
                                $namespace = $matches["namespace"];
                                if ($this->_isCacheOfCurrentTracker($namespace)) {
                                    $namespaceVersion = file_get_contents($name);
                                    $namespaceVersion = unserialize(explode("\n", $namespaceVersion)[1]);
                                    $namespacesVersions[$namespace] = $namespaceVersion;
                                }
                            }
                        } else {
                            if (preg_match("/(?<namespace>\\w+)\\[.*\\[(?<version>\\d+)\\]/", $decodedFilename, $filenameMatches)) {
                                $filenamesByNamespaceVersions[$filenameMatches["namespace"]][$filenameMatches["version"]][] = $name;
                            }
                        }
                    }
                }
            }
        }
        foreach ($namespacesVersions as $namespace => $version) {
            if (isset($filenamesByNamespaceVersions[$namespace])) {
                foreach ($filenamesByNamespaceVersions[$namespace] as $filesVersion => $files) {
                    if ($filesVersion < $version - 1) {
                        foreach ($files as $file) {
                            $fsService->remove($file);
                        }
                    }
                }
            }
        }
        return true;
    }
    private function _flushOldNamespacedRedisCache()
    {
        if (get_class($this->_doctrineCache) !== "Doctrine\\Common\\Cache\\RedisCache") {
            return false;
        }
        $namespaceCacheKeyStart = substr(\Doctrine\Common\Cache\CacheProvider::DOCTRINE_NAMESPACE_CACHEKEY, 0, -4);
        $namespacesVersions = [];
        $redis = $this->_doctrineCache->getRedis();
        $iterator = NULL;
        while (($keys = $redis->scan($iterator, $namespaceCacheKeyStart . "*")) !== false) {
            foreach ($keys as $key) {
                if (preg_match("/" . $namespaceCacheKeyStart . "\\[(?<namespace>\\w+)\\]/", $key, $matches)) {
                    $namespace = $matches["namespace"];
                    if ($this->_isCacheOfCurrentTracker($namespace)) {
                        $namespacesVersions[$namespace] = $redis->get($key);
                    }
                }
            }
        }
        foreach ($namespacesVersions as $namespace => $version) {
            if ($version > 2) {
                $lowerVersions = array_reverse(range(1, $version - 2));
                foreach ($lowerVersions as $lowerVersion) {
                    $iterator = NULL;
                    $matchPattern = $namespace . "\\[*\\]\\[" . $lowerVersion . "\\]";
                    while (($keys = $redis->scan($iterator, $matchPattern)) !== false) {
                        $redis->del($keys);
                    }
                }
            }
        }
        return true;
    }
    private function _isCacheOfCurrentTracker($namespace)
    {
        return strpos($namespace, $this->_namespaceHash) !== false;
    }
}

?>