<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Editor\Controller;

class EditorController extends \Admin\Controller\BaseController
{
    public function loadFilesAction()
    {
        $id = (int) $this->getParam("id");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $model = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($model)) {
            $this->throwDeny();
        }
        return $repository->loadStructure($model);
    }
    public function loadFileDataAction()
    {
        $id = (int) $this->getParam("id");
        $path = $this->getParam("path");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $model = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($model)) {
            $this->throwDeny();
        }
        $data = $repository->loadFileData($model, $path);
        return ["data" => $data];
    }
    public function saveFileDataAction()
    {
        $id = (int) $this->getParam("id");
        $path = $this->getParam("path");
        $data = $this->getParam("data");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $service = new \Component\Editor\Service\EditorService();
        $model = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($model)) {
            $this->throwDeny();
        }
        $result = $service->saveFile($model, $path, $data);
        if ($result) {
            $domain = \Traffic\Service\UrlService::instance()->stripHostWww($this->getServerRequest()->getUri());
            $landing = $repository->findModel($id, $type);
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($landing->getFolder());
            \Component\Landings\DelayedCommand\CreatePreviewImageCommand::enqueue($domain, $systemPath);
        }
        return ["path" => $result];
    }
    public function createFileAction()
    {
        $id = (int) $this->getParam("id");
        $path = $this->getParam("path");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $service = new \Component\Editor\Service\EditorService();
        $model = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($model)) {
            $this->throwDeny();
        }
        $pathFile = $service->createFile($model, $path);
        return ["path" => $pathFile];
    }
    public function removeFileAction()
    {
        $id = (int) $this->getParam("id");
        $path = $this->getParam("path");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $service = new \Component\Editor\Service\EditorService();
        $model = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($model)) {
            $this->throwDeny();
        }
        $result = $service->deleteFile($model, $path);
        if ($result) {
            $domain = \Traffic\Service\UrlService::instance()->stripHostWww($this->getServerRequest()->getUri());
            $landing = $repository->findModel($id, $type);
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($landing->getFolder());
            \Component\Landings\DelayedCommand\CreatePreviewImageCommand::enqueue($domain, $systemPath);
        }
        return ["success" => $result];
    }
    public function infoLandingAction()
    {
        $id = (int) $this->getParam("id");
        $type = $this->getParam("type");
        $repository = new \Component\Editor\Repository\EditorRepository();
        $landing = $repository->findModel($id, $type);
        if (!$this->isEditAllowed($landing)) {
            $this->throwDeny();
        }
        if (empty($landing)) {
            $this->throwNotFound();
        }
        if (!$this->isViewAllowed($landing)) {
            $this->throwDeny();
        }
        if ($type === \Component\Editor\Repository\EditorRepository::MODEL_OFFER) {
            $serializer = new \Component\Offers\Serializer\OfferSerializer();
        } else {
            $serializer = new \Component\Landings\Serializer\LandingSerializer();
        }
        return $this->serialize($landing, $serializer);
    }
}

?>