<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Service;

class ExportedReportsService extends \Traffic\Service\AbstractService
{
    private $_path = NULL;
    const FILE_TTL_DAYS = 3;
    public function __construct()
    {
        $this->_path = ROOT . "/exports/";
    }
    public function setPath($path)
    {
        $this->_path = $path;
    }
    public function delete($filename)
    {
        $user = \Component\Users\Service\CurrentUserService::instance()->get();
        $id = $user->getId();
        $pattern = "report_" . $id . "_";
        if (!$user->isAdmin() && substr($filename, 0, strlen($pattern)) !== $pattern) {
            throw new \Core\Exceptions\DenyError("Access denied");
        }
        $path = $this->_path . $filename;
        if (!is_file($path)) {
            throw new \Core\Exceptions\NotFoundError("File " . $path . " not found");
        }
        return unlink($path);
    }
    public function deleteAll()
    {
        $files = \Component\Reports\Repository\ExportedReportsRepository::instance()->all();
        foreach ($files as $file) {
            $this->delete($file["filename"]);
        }
    }
    public function pruneOldFiles(\DateTime $now)
    {
        $time = $now->getTimestamp();
        $dir = $this->_path;
        if (is_dir($dir)) {
            $directory = dir($dir);
            while ($fileName = $directory->read()) {
                $file = $dir . $fileName;
                if (is_file($file) && FILE_TTL_DAYS * 60 * 60 * 24 < $time - filemtime($file)) {
                    unlink($file);
                }
            }
        }
    }
}

?>