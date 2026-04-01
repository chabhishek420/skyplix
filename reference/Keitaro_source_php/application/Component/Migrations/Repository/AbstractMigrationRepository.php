<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Repository;

abstract class AbstractMigrationRepository extends \Traffic\Repository\AbstractBaseRepository
{
    protected $_path = NULL;
    protected $_items = [];
    public function __construct()
    {
        $this->init();
    }
    public function getPath()
    {
        return $this->_path;
    }
    public function getItems()
    {
        return $this->_items;
    }
    public function getItem($key)
    {
        if (isset($this->_items[$key])) {
            return $this->_items[$key];
        }
    }
    public function getKeys()
    {
        return array_keys($this->_items);
    }
    public function isExists($name)
    {
        return isset($this->_items[$name]);
    }
    public function setPath($path)
    {
        $this->_path = $path;
        $this->init();
    }
    public function init()
    {
        $this->_items = [];
        $this->_loadFromPath($this->getPath());
    }
    protected function _loadFromPath($path)
    {
        if (!is_dir($path)) {
            throw new \Exception("No dir" . $path);
        }
        $d = dir($path);
        while (false !== ($entry = $d->read())) {
            if ($entry != ".." && $entry != "." && strstr($entry, ".php")) {
                $filePath = $path . "/" . $entry;
                $key = str_replace(".php", "", $entry);
                $this->_load($filePath, $key);
            }
        }
        $d->close();
        ksort($this->_items);
        return $this->_items;
    }
    protected abstract function _load($filePath, $key);
}

?>