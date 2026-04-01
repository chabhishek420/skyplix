<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Controller;

class LandingsController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function gridDefinitionAction()
    {
        $definition = new \Component\Landings\Grid\LandingGridDefinition();
        return $definition->getGridDefinition();
    }
    public function indexAction()
    {
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allNotDeletedWithRelations("id");
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, false, $this->getUser());
        return $this->serialize($landings, new \Component\Landings\Serializer\LandingSerializer($this->getParam("withGroupName")));
    }
    public function listAsOptionsAction()
    {
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allActive();
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, false, $this->getUser());
        return \Component\Landings\Repository\LandingRepository::instance()->listAsOptions($landings);
    }
    public function withStatsAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Landings\Repository\LandingRepository::instance()->allWithStats($this->getPostParams(), $userParams);
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $landing = \Component\Landings\Repository\LandingRepository::instance()->find($id);
        if (empty($landing)) {
            $this->throwNotFound();
        }
        if (!$this->isViewAllowed($landing)) {
            $this->throwDeny();
        }
        return $this->serialize($landing, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function createAction()
    {
        $data = $this->getPostParams();
        if (\Traffic\Service\ConfigService::instance()->isDemo() && !empty($data["archive"])) {
            $this->throwDenyBecauseDemo();
        }
        if (!$this->isCreateAllowed(\Traffic\Model\Landing::aclKey())) {
            $this->throwDeny();
        }
        $landing = \Component\Landings\Service\LandingService::instance()->create($data);
        if ($landing->isLocal()) {
            $domain = \Core\Application\LicenseService::instance()->getLicenseIp();
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($landing->getFolder());
            \Component\Landings\LocalFile\PreviewImageService::instance()->createPreview($domain, $systemPath);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$landing], false);
        return $this->serialize($landing, new \Component\Landings\Serializer\LandingSerializer(true));
    }
    public function updateAction()
    {
        $data = $this->getPostParams();
        if (\Traffic\Service\ConfigService::instance()->isDemo() && !empty($data["archive"])) {
            $this->throwDenyBecauseDemo();
        }
        $id = (int) $this->getParam("id");
        $landing = \Component\Landings\Repository\LandingRepository::instance()->find($id);
        if (!$this->isEditAllowed($landing)) {
            $this->throwDeny();
        }
        $landing = \Component\Landings\Service\LandingService::instance()->update($landing, $data);
        if ($landing->isLocal()) {
            $domain = \Core\Application\LicenseService::instance()->getLicenseIp();
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($landing->getFolder());
            \Component\Landings\LocalFile\PreviewImageService::instance()->createPreview($domain, $systemPath);
        }
        return $this->serialize($landing, new \Component\Landings\Serializer\LandingSerializer(true));
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allByIds($ids);
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, true, $this->getUser());
        foreach ($landings as $landing) {
            \Component\Landings\Service\LandingService::instance()->archive($landing);
        }
        return $this->serialize($landings, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function cloneAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Landing::aclKey())) {
            $this->throwDeny();
        }
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $oldLandings = \Component\Landings\Repository\LandingRepository::instance()->allByIds($ids);
        $oldLandings = \Component\Users\Service\AclService::instance()->filterByAcl($oldLandings, false, $this->getUser());
        $landings = [];
        foreach ($oldLandings as $landing) {
            $landings[] = \Component\Landings\Service\LandingService::instance()->cloneResource($landing);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $landings, false);
        return $this->serialize($landings, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function deletedAction()
    {
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allDeleted();
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, false, $this->getUser());
        return $this->serialize($landings, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function restoreAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allByIds($ids);
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, true, $this->getUser());
        foreach ($landings as $landing) {
            \Component\Landings\Service\LandingService::instance()->makeActive($landing);
        }
        return $this->serialize($landings, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $landings = \Component\Landings\Repository\LandingRepository::instance()->allByIds([$id]);
        $landings = \Component\Users\Service\AclService::instance()->filterByAcl($landings, true, $this->getUser());
        $items = [];
        foreach ($landings as $landing) {
            $items[] = \Component\Landings\Service\LandingService::instance()->updateNote($landing, $note);
        }
        return $this->serialize($items, new \Component\Landings\Serializer\LandingSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Landing::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\Landings\PruneTask\PruneLandings();
        $pruner->deleteAll();
    }
    public function downloadAction()
    {
        $id = (int) $this->getParam("id");
        $type = \Component\Editor\Repository\EditorRepository::MODEL_LANDING;
        $downloaderService = new \Component\Landings\Service\LandingDownloaderService();
        $file = $downloaderService->getPackedFile($id, $type);
        foreach ($downloaderService->getHeadersDownload($file["name"]) as $header => $value) {
            $this->header($header, $value);
        }
        return file_get_contents($file["path"]);
    }
}

?>