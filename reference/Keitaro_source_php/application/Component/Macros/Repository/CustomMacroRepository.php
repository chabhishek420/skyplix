<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Macros\Repository;

class CustomMacroRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private function _compatibilityWrap($name, \SplFileInfo $file)
    {
        $className = $name . "_macros";
        if (class_exists($className)) {
            return new $className();
        }
    }
    private function _shouldLoad(\SplFileInfo $file)
    {
        return $file->isFile() && !strstr($file->getFilename(), "AbstractMacros") && !strstr($file->getFilename(), "BaseMacros") && substr($file->getFilename(), -3, 3) === "php";
    }
    public function getCustomMacros($customPath, $macroNames)
    {
        $result = [];
        $iterator = new \DirectoryIterator($customPath);
        foreach ($iterator as $file) {
            if ($this->_shouldLoad($file)) {
                $name = str_replace(".php", "", $file->getFilename());
                if (!(isset($macroNames[\Traffic\Macros\MacroRepository::CLICK][$name]) || isset($macroNames[\Traffic\Macros\MacroRepository::CONVERSION][$name]) || $name == "extra_param_6")) {
                    require_once $file->getPathname();
                    $className = "\\Macros\\" . $name;
                    if (class_exists($className, false)) {
                        $macro = new $className();
                    } else {
                        $macro = $this->_compatibilityWrap($name, $file);
                    }
                    if ($macro instanceof \BaseMacros) {
                        \Traffic\Logging\Service\LoggerService::instance()->error("Macro '" . $name . "' uses outdated BaseMacros. " . "Please update this macros (help.keitaro.io/ru/macros).");
                    }
                    if (empty($macro)) {
                        $msg = "File " . $file . " must contain class name " . $className;
                        throw new \Core\Application\Exception\Error($msg);
                    }
                    $result[$name] = $macro;
                }
            }
        }
        return $result;
    }
}

?>