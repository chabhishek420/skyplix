<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Editor\Repository;

class EditorRepository
{
    const MODEL_LANDING = "landing";
    const MODEL_OFFER = "offer";
    public function loadStructure(\Core\Model\AbstractModel $model)
    {
        $folder = \Component\Landings\LocalFile\LocalFileService::instance()->getStoragePath();
        if ($model) {
            $option = $model->get("action_options");
            $this->checkLocalType($option);
            if (isset($option["folder"])) {
                $path = $folder . "/" . $option["folder"];
                $files = new \Symfony\Component\Finder\Finder();
                $structureFiles = $files->files()->in($path);
                $folders = new \Symfony\Component\Finder\Finder();
                $structureDirs = $folders->directories()->in($path);
                $files = [];
                $dirs = [];
                foreach ($structureFiles as $item) {
                    $files[] = $item->getRelativePathname();
                }
                foreach ($structureDirs as $item) {
                    $dirs[] = $item->getRelativePathname();
                }
                return $this->_createStructure($dirs, $files, $option["folder"]);
            }
        }
    }
    public function loadFileData(\Core\Model\AbstractModel $model, $filePath)
    {
        $folder = \Component\Landings\LocalFile\LocalFileService::instance()->getStoragePath();
        if ($model) {
            $option = $model->get("action_options");
            $this->checkLocalType($option);
            $path = $folder . "/" . $option["folder"] . "/" . $filePath;
            $data = file_get_contents($path);
            return str_replace("\r", "", $data);
        }
    }
    private function _createStructure($dirs, $files, $root)
    {
        $resultDirs = [];
        $resultFiles = [];
        foreach ($dirs as $dir) {
            $resultDirs = $this->_createArrayDir($dir, $resultDirs);
        }
        foreach ($files as $dir) {
            $resultFiles = $this->_createArrayFiles($dir, $resultFiles);
        }
        $arrayData = array_merge_recursive($resultDirs, $resultFiles);
        foreach ($arrayData as $key => $arrayDatum) {
            $arrayData[$key] = $this->_reqv($arrayDatum);
        }
        return ["name" => $root, "toggled" => true, "type" => "root", "children" => array_values($arrayData)];
    }
    private function _createArrayDir($path, $result)
    {
        $data = explode("/", $path);
        $link =& $result;
        foreach ($data as $key => $item) {
            if ($key !== count($data) - 1) {
                if (!isset($link["k" . $item])) {
                    $link["k" . $item] = ["name" => $item, "type" => "folder", "children" => [], "path" => $path];
                }
                $link =& $link["k" . $item]["children"];
            } else {
                $link["k" . $item] = ["name" => $item, "type" => "folder", "children" => [], "path" => $path];
            }
        }
        return array_reverse($result, true);
    }
    private function _createArrayFiles($path, $result)
    {
        $data = explode("/", $path);
        $link =& $result;
        foreach ($data as $key => $item) {
            if ($key !== count($data) - 1) {
                $link =& $link["k" . $item]["children"];
            } else {
                $ext = pathinfo($path, PATHINFO_EXTENSION);
                $link["k" . $item] = ["name" => $item, "type" => "file", "path" => $path, "ext" => $ext];
            }
        }
        return array_reverse($result, true);
    }
    private function _reqv($arrayData)
    {
        foreach ($arrayData as $key => $arrayDatum) {
            if ($key === "children") {
                $arrayData[$key] = array_values($arrayData[$key]);
                foreach ($arrayData[$key] as $key1 => $arrayDatum1) {
                    $arrayData[$key][$key1] = $this->_reqv($arrayDatum1);
                }
            }
        }
        return $arrayData;
    }
    public function checkLocalType($option)
    {
        if (!isset($option["folder"])) {
            throw new \Core\Validator\ValidationError(["error" => "Only local landing available"]);
        }
    }
    public function findModel($id, $type)
    {
        switch ($type) {
            case MODEL_LANDING:
                return \Component\Landings\Repository\LandingRepository::instance()->find($id);
                break;
            case MODEL_OFFER:
                return \Component\Offers\Repository\OfferRepository::instance()->find($id);
                break;
            default:
                return false;
        }
    }
}

?>