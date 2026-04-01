<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\DownloadManager;

abstract class DownloadManager implements DownloadManagerInterface
{
    private $_definition = NULL;
    private $_options = NULL;
    public function __construct(\Component\GeoDb\GeoDbDefinition $definition, $options = NULL)
    {
        $this->_definition = $definition;
        $this->_options = $options;
    }
    public function definition()
    {
        return $this->_definition;
    }
    public function options()
    {
        return $this->_options;
    }
    public function getFilePath()
    {
        return $this->definition()->filePath();
    }
    public function option($name)
    {
        if (!$this->hasOption($name)) {
            throw new \Exception("No option '" . $name . "' in download manager for '" . $this->definition()->id() . "''");
        }
        return $this->_options[$name];
    }
    public function hasOption($name)
    {
        return trim($this->_options[$name]);
    }
    public function timestamp()
    {
        if (file_exists($this->getFilePath())) {
            $time = new \DateTime();
            $timestamp = filemtime($this->getFilePath());
            $time->setTimestamp($timestamp);
            return $time;
        }
        return NULL;
    }
}

?>