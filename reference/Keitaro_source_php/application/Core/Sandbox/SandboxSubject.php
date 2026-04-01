<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox;

class SandboxSubject
{
    private $_code = NULL;
    private $_filePath = NULL;
    private $_layoutPath = NULL;
    public static function withCode($code)
    {
        $subject = new SandboxSubject();
        return $subject->setCode($code);
    }
    public static function fromFile($filePath, $layoutPath = NULL)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File '" . $filePath . "' not found'");
        }
        if (!empty($layoutPath) && !file_exists($layoutPath)) {
            throw new \Exception("File '" . $layoutPath . "' not found'");
        }
        $subject = new SandboxSubject();
        $subject->setFilePath($filePath)->setLayoutPath($layoutPath)->setCode(file_get_contents($filePath));
        return $subject;
    }
    public function setFilePath($path)
    {
        $this->_filePath = $path;
        return $this;
    }
    public function setLayoutPath($layoutPath)
    {
        $this->_layoutPath = $layoutPath;
        return $this;
    }
    public function layoutPath()
    {
        return $this->_layoutPath;
    }
    public function setCode($code)
    {
        $this->_code = $code;
        return $this;
    }
    public function code()
    {
        return $this->_code;
    }
    public function filePath()
    {
        return $this->_filePath;
    }
    public function dirPath()
    {
        return dirname($this->filePath());
    }
    public function localPath()
    {
        return \Component\Landings\LocalFile\LocalFileService::instance()->absoluteToLocalPath($this->dirPath());
    }
}

?>