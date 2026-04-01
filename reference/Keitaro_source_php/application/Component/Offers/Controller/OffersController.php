<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Controller;

class OffersController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function gridDefinitionAction()
    {
        $definition = new \Component\Offers\Grid\OfferGridDefinition();
        return $definition->getGridDefinition();
    }
    public function indexAction()
    {
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allNotDeletedWithRelations("id");
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, false, $this->getUser());
        return $this->serialize($offers, new \Component\Offers\Serializer\OfferSerializer($this->getParam("withGroupName")));
    }
    public function listAsOptionsAction()
    {
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allActive();
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, false, $this->getUser());
        return \Component\Offers\Repository\OfferRepository::instance()->listAsOptions($offers);
    }
    public function withStatsAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\Offers\Repository\OfferRepository::instance()->allWithStats($this->getPostParams(), $userParams);
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $offer = \Component\Offers\Repository\OfferRepository::instance()->find($id);
        if (!$this->isViewAllowed($offer)) {
            $this->throwDeny();
        }
        return $this->serialize($offer, new \Component\Offers\Serializer\OfferSerializer($this->getParam("withGroupName")));
    }
    public function createAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Offer::aclKey())) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $offer = \Component\Offers\Service\OfferService::instance()->create($data);
        if ($offer->isLocal()) {
            $domain = \Core\Application\LicenseService::instance()->getLicenseIp();
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($offer->getFolder());
            \Component\Landings\LocalFile\PreviewImageService::instance()->createPreview($domain, $systemPath);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$offer], false);
        return $this->serialize($offer, new \Component\Offers\Serializer\OfferSerializer(true));
    }
    public function updateAction()
    {
        $id = (int) $this->getParam("id");
        $offer = \Component\Offers\Repository\OfferRepository::instance()->find($id);
        if (!$this->isEditAllowed($offer)) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $offer = \Component\Offers\Service\OfferService::instance()->update($offer, $data);
        if ($offer->isLocal()) {
            $domain = \Core\Application\LicenseService::instance()->getLicenseIp();
            $systemPath = \Component\Landings\LocalFile\LocalFileService::instance()->buildPath($offer->getFolder());
            \Component\Landings\LocalFile\PreviewImageService::instance()->createPreview($domain, $systemPath);
        }
        return $this->serialize($offer, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allByIds($ids);
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, true, $this->getUser());
        foreach ($offers as $offer) {
            \Component\Offers\Service\OfferService::instance()->archive($offer);
        }
        return $this->serialize($offers, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function cloneAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Offer::aclKey())) {
            $this->throwDeny();
        }
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $oldOffers = \Component\Offers\Repository\OfferRepository::instance()->allByIds($ids);
        $oldOffers = \Component\Users\Service\AclService::instance()->filterByAcl($oldOffers, true, $this->getUser());
        $offers = [];
        foreach ($oldOffers as $offer) {
            $offers[] = \Component\Offers\Service\OfferService::instance()->cloneResource($offer);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $offers, false);
        return $this->serialize($offers, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function deletedAction()
    {
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allDeleted();
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, false, $this->getUser());
        return $this->serialize($offers, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function restoreAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allByIds($ids);
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, true, $this->getUser());
        foreach ($offers as $offer) {
            \Component\Offers\Service\OfferService::instance()->makeActive($offer);
        }
        return $this->serialize($offers, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function getCostTypesAction()
    {
        return \Component\Offers\Repository\OfferRepository::instance()->getCostTypes();
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $offers = \Component\Offers\Repository\OfferRepository::instance()->allByIds([$id]);
        $offers = \Component\Users\Service\AclService::instance()->filterByAcl($offers, true, $this->getUser());
        $items = [];
        foreach ($offers as $offer) {
            $items[] = \Component\Offers\Service\OfferService::instance()->updateNote($offer, $note);
        }
        return $this->serialize($items, new \Component\Offers\Serializer\OfferSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Offer::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\Offers\PruneTask\PruneOffers();
        $pruner->deleteAll();
    }
    public function downloadAction()
    {
        $id = (int) $this->getParam("id");
        $type = \Component\Editor\Repository\EditorRepository::MODEL_OFFER;
        $downloaderService = new \Component\Landings\Service\LandingDownloaderService();
        $file = $downloaderService->getPackedFile($id, $type);
        foreach ($downloaderService->getHeadersDownload($file["name"]) as $header => $value) {
            $this->header($header, $value);
        }
        return file_get_contents($file["path"]);
    }
}

?>