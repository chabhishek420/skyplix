<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class PageInfo
{
    private $_uri = NULL;
    private $_path = NULL;
    private $_indexFile = NULL;
    private $_folder = NULL;
    private $_phpAllowed = NULL;
    private function allowedOptions()
    {
        return ["folder"];
    }
    public function __construct(\Psr\Http\Message\UriInterface $requestUri, $pageOptions, $phpAllowed = false)
    {
        foreach ($pageOptions as $key => $value) {
            $name = "_" . \Traffic\Tools\Tools::toCamelCase($key, true);
            if (!in_array($key, $this->allowedOptions())) {
                throw new \Exception("Incorrect option " . $key);
            }
            $this->{$name} = $value;
        }
        if (empty($this->_folder)) {
            throw new \Exception("folder is not set");
        }
        $this->_path = LocalFileService::instance()->buildPath($this->_folder);
        $this->_uri = $requestUri->withPath(LocalFileService::instance()->absoluteToLocalPath($this->_path));
        $this->_indexFile = LocalFileService::instance()->findIndexFile($this->_path);
        $this->_phpAllowed = $phpAllowed;
    }
    public function uri()
    {
        $localPath = LocalFileService::instance()->absoluteToLocalPath($this->indexFilePath());
        return $this->_uri->withPath($localPath);
    }
    public function isPhpAllowed()
    {
        return $this->_phpAllowed;
    }
    public function indexFilePath()
    {
        return $this->_path . "/" . $this->_indexFile;
    }
}

?>