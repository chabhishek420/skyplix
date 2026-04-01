<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid;

class ReportSaver
{
    private $_format = NULL;
    private $_fileName = NULL;
    private $_path = NULL;
    public function __construct($format, $path = "/exports")
    {
        $this->_format = $format;
        $this->_fileName = $this->_generateFileName();
        $this->_path = $path;
        if (!is_dir(ROOT . "/" . $this->_path)) {
            mkdir(ROOT . "/" . $this->_path);
        }
        if (!is_writable(ROOT . "/" . $this->_path)) {
            throw new Error(\Core\Locale\LocaleService::t("grid.save_error", [ROOT . $this->_path]));
        }
    }
    public function getFilePath()
    {
        return $this->_path . "/" . $this->_fileName;
    }
    public function getSystemFilePath()
    {
        return ROOT . "/" . $this->_path . "/" . $this->_fileName;
    }
    public function getFileName()
    {
        return $this->_fileName;
    }
    public function append($text)
    {
        if (!empty($text) && !file_put_contents($this->getSystemFilePath(), $text . "\n", FILE_APPEND)) {
            throw new Error("Cant's save file \"" . $this->getSystemFilePath() . "\"");
        }
    }
    public function save($binaryContent)
    {
        if (!empty($binaryContent) && !file_put_contents($this->getSystemFilePath(), $binaryContent, FILE_BINARY)) {
            throw new Error("Cant's save file \"" . $this->getSystemFilePath() . "\"");
        }
    }
    public function getUrl()
    {
        return "/exports/" . $this->_fileName;
    }
    private function _generateFileName()
    {
        $prefix = "report_";
        if (\Component\Users\Service\CurrentUserService::instance()->exists()) {
            $prefix .= \Component\Users\Service\CurrentUserService::instance()->get()->getId() . "_";
        }
        $fileName = $prefix . date("Y-m-d_H-i-s") . "." . $this->_format;
        return $fileName;
    }
}

?>