<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Editor\Service;

class EditorService
{
    const AVAILABLE_EXTENSION = ["php", "html", "css", "js", "txt"];
    public function saveFile(\Core\Model\AbstractModel $model, $path, $data)
    {
        $folder = \Component\Landings\LocalFile\LocalFileService::instance()->getStoragePath();
        $repository = new \Component\Editor\Repository\EditorRepository();
        if ($model) {
            $option = $model->get("action_options");
            $repository->checkLocalType($option);
            $pathFull = $folder . "/" . $option["folder"] . "/" . $path;
            \Core\FileSystem\Service\FileSystemService::instance()->dumpFile($pathFull, $data);
            return $pathFull;
        }
        return false;
    }
    public function createFile(\Core\Model\AbstractModel $model, $path)
    {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        if (in_array($ext, AVAILABLE_EXTENSION)) {
            if ($ext === "php" && !\Component\Landings\LocalFile\LocalFileService::instance()->isPhpAllowed()) {
                throw new \Core\Validator\ValidationError("PHP is not allowed");
            }
            $folder = \Component\Landings\LocalFile\LocalFileService::instance()->getStoragePath();
            $repository = new \Component\Editor\Repository\EditorRepository();
            if ($model) {
                $option = $model->get("action_options");
                $repository->checkLocalType($option);
                $pathFull = $folder . "/" . $option["folder"] . "/" . $path;
                \Core\FileSystem\Service\FileSystemService::instance()->dumpFile($pathFull, "");
                return $pathFull;
            }
        }
        throw new \Core\Validator\ValidationError("Validation error");
    }
    public function deleteFile(\Core\Model\AbstractModel $model, $path)
    {
        $folder = \Component\Landings\LocalFile\LocalFileService::instance()->getStoragePath();
        $repository = new \Component\Editor\Repository\EditorRepository();
        if ($model) {
            $option = $model->get("action_options");
            $repository->checkLocalType($option);
            $pathFull = $folder . "/" . $option["folder"] . "/" . $path;
            \Core\FileSystem\Service\FileSystemService::instance()->remove($pathFull);
            return true;
        }
        return false;
    }
}

?>