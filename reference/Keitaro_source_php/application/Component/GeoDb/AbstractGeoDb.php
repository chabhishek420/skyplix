<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb;

abstract class AbstractGeoDb
{
    private $_definition = NULL;
    private $_manager = NULL;
    private $_adapter = NULL;
    public function setDefinition(GeoDbDefinition $definition)
    {
        $this->_definition = $definition;
    }
    public function setManager(DownloadManager\DownloadManagerInterface $manager)
    {
        $this->_manager = $manager;
    }
    public function setAdapter(Adapter\GeoDbAdapterInterface $adapter)
    {
        $this->_adapter = $adapter;
    }
    public function definition()
    {
        if (empty($this->_definition)) {
            throw new \Exception("GeoDbDefinition is not defined for " . get_class($this));
        }
        return $this->_definition;
    }
    public function manager()
    {
        if (empty($this->_manager)) {
            throw new \Exception("Manager is not defined for " . get_class($this));
        }
        return $this->_manager;
    }
    public function adapter()
    {
        if (empty($this->_definition)) {
            throw new \Exception("GeoDbAdapter is not defined for " . get_class($this));
        }
        return $this->_adapter;
    }
    public function isUpdateAvailable()
    {
        return $this->manager()->isUpdateAvailable();
    }
    public function info($ip)
    {
        return $this->adapter()->info($ip);
    }
    public function rawInfo($ip)
    {
        return $this->adapter()->rawInfo($ip);
    }
    public function exists()
    {
        return file_exists($this->definition()->filePath());
    }
}

?>