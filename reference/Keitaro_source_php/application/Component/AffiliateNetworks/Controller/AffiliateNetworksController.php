<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AffiliateNetworks\Controller;

class AffiliateNetworksController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function gridDefinitionAction()
    {
        $definition = new \Component\AffiliateNetworks\Grid\AffiliateNetworkGridDefinition();
        return $definition->getGridDefinition();
    }
    public function indexAction()
    {
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allActive();
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, false, $this->getUser());
        return $this->serialize($networks, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer(true));
    }
    public function withStatsAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allWithStats($this->getPostParams(), $userParams);
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $network = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->find($id);
        if (!$this->isViewAllowed($network)) {
            $this->throwDeny();
        }
        return $this->serialize($network, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function listAsOptionsAction()
    {
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allActive();
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, false, $this->getUser());
        return \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->listAsOptions($networks);
    }
    public function createAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\AffiliateNetwork::aclKey())) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $network = \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->create($data);
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$network], false);
        return $this->serialize($network, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function updateAction()
    {
        $id = (int) $this->getParam("id");
        $network = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->find($id);
        if (!$this->isEditAllowed($network)) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $network = \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->update($network, $data);
        return $this->serialize($network, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allByIds($ids);
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, true, $this->getUser());
        foreach ($networks as $network) {
            \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->archive($network);
        }
        return $this->serialize($networks, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function cloneAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\AffiliateNetwork::aclKey())) {
            $this->throwDeny();
        }
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $oldNetworks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allByIds($ids);
        $oldNetworks = \Component\Users\Service\AclService::instance()->filterByAcl($oldNetworks, false, $this->getUser());
        $networks = [];
        foreach ($oldNetworks as $network) {
            $data = [];
            $data["name"] = \Core\Entity\Service\EntityService::COPY_OF . $network->get("name");
            $networks[] = \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->cloneEntity($network, $data);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $networks, false);
        return $this->serialize($networks, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function deletedAction()
    {
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allDeleted();
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, false, $this->getUser());
        return $this->serialize($networks, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function restoreAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allByIds($ids);
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, true, $this->getUser());
        $items = [];
        foreach ($networks as $network) {
            $items[] = \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->makeActive($network);
        }
        return $this->serialize($items, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $networks = \Component\AffiliateNetworks\Repository\AffiliateNetworksRepository::instance()->allByIds([$id]);
        $networks = \Component\Users\Service\AclService::instance()->filterByAcl($networks, true, $this->getUser());
        $items = [];
        foreach ($networks as $network) {
            $items[] = \Component\AffiliateNetworks\Service\AffiliateNetworkService::instance()->updateNote($network, $note);
        }
        return $this->serialize($items, new \Component\AffiliateNetworks\Serializer\AffiliateNetworkSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\AffiliateNetwork::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\AffiliateNetworks\PruneTask\PruneAffiliateNetworks();
        $pruner->deleteAll();
    }
}

?>