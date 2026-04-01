<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamActions\Repository;

class CustomStreamActionRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private function _shouldLoad(\SplFileInfo $file)
    {
        return $file->isFile() && !strstr($file->getFilename(), "Abstract") && !strstr($file->getFilename(), "Base") && !strstr($file->getFilename(), "DS_Store") && substr($file->getFilename(), -3, 3) === "php";
    }
    public function getCustomStreamActions($customPath, $actions)
    {
        $result = [];
        if (!\Core\Application\FeatureService::instance()->hasExtensionsFeature()) {
            return $result;
        }
        $iterator = new \DirectoryIterator($customPath);
        foreach ($iterator as $file) {
            if ($this->_shouldLoad($file)) {
                $name = str_replace(".php", "", $file->getFilename());
                $className = "\\Redirects\\" . $name;
                if (!(isset($actions[$name]) || $name == "stream_id" || $name == "stream")) {
                    require_once $file->getPathname();
                    if (!class_exists($className, false)) {
                        $msg = "File " . $file . " must contain '" . $className . "' class";
                        throw new \Traffic\Actions\ActionError($msg);
                    }
                    $redirect = new $className();
                    $result[$name] = $redirect;
                    if (empty($redirect)) {
                        $msg = "File " . $file . " must contain class name " . $className;
                        throw new \Traffic\Actions\ActionError($msg);
                    }
                }
            }
        }
        return $result;
    }
}

?>