<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Repository;

class ExportedReportsRepository extends \Traffic\Service\AbstractService
{
    private $_path = NULL;
    public function __construct()
    {
        $this->_path = ROOT . "/exports/";
    }
    public function setPath($path)
    {
        $this->_path = $path;
    }
    public function all()
    {
        $user = \Component\Users\Service\CurrentUserService::instance()->get();
        $result = [];
        if (!is_dir($this->_path)) {
            return [];
        }
        $directory = new \DirectoryIterator($this->_path);
        if ($user->isAdmin()) {
            $regexp = "#^report_\\d+_#";
        } else {
            $regexp = "#^report_" . $user->getId() . "_#";
        }
        $files = new \RegexIterator($directory, $regexp);
        foreach ($files as $file) {
            if ($file->isFile()) {
                $date = new \DateTime();
                $date->setTimestamp($file->getMTime());
                $result[] = ["filename" => $file->getFilename(), "datetime" => $date->format(\Core\Model\AbstractModel::DATETIME_FORMAT)];
            }
        }
        return $result;
    }
}

?>