<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Controller;

class TrafficSourcesController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function gridDefinitionAction()
    {
        $definition = new \Component\TrafficSources\Grid\TrafficSourceGridDefinition();
        return $definition->getGridDefinition();
    }
    public function indexAction()
    {
        $sources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allActive();
        $sources = \Component\Users\Service\AclService::instance()->filterByAcl($sources, false, $this->getUser());
        return $this->serialize($sources, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function withStatsAction()
    {
        $userParams = \Component\Grid\QueryParams\UserParams::create($this);
        return \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allWithStats($this->getPostParams(), $userParams);
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $source = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->find($id);
        if ($source && !$this->isViewAllowed($source)) {
            $this->throwDeny();
        }
        return $this->serialize($source, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function listAsOptionsAction()
    {
        $sources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allActive();
        $sources = \Component\Users\Service\AclService::instance()->filterByAcl($sources, false, $this->getUser());
        return \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->listAsOptions($sources);
    }
    public function postbackStatusesAction()
    {
        return \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->getPostbackStatuses();
    }
    public function createAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\TrafficSource::aclKey())) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $source = \Component\TrafficSources\Service\TrafficSourceService::instance()->create($data);
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), [$source], false);
        return $this->serialize($source, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function updateAction()
    {
        $id = (int) $this->getParam("id");
        $source = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->find($id);
        if (!$this->isEditAllowed($source)) {
            $this->throwDeny();
        }
        $data = $this->getPostParams();
        $source = \Component\TrafficSources\Service\TrafficSourceService::instance()->updateTrafficSource($source, $data);
        return $this->serialize($source, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $sources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allByIds($ids);
        $sources = \Component\Users\Service\AclService::instance()->filterByAcl($sources, true, $this->getUser());
        foreach ($sources as $source) {
            \Component\TrafficSources\Service\TrafficSourceService::instance()->archiveTrafficSource($source);
        }
        return $this->serialize($sources, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function cloneAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\TrafficSource::aclKey())) {
            $this->throwDeny();
        }
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $oldSources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allByIds($ids);
        $oldSources = \Component\Users\Service\AclService::instance()->filterByAcl($oldSources, false, $this->getUser());
        $sources = [];
        foreach ($oldSources as $source) {
            $data = ["name" => \Core\Entity\Service\EntityService::COPY_OF . $source->get("name")];
            $sources[] = \Component\TrafficSources\Service\TrafficSourceService::instance()->cloneEntity($source, $data);
        }
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $sources, false);
        return $this->serialize($sources, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function deletedAction()
    {
        $sources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allDeleted();
        $sources = \Component\Users\Service\AclService::instance()->filterByAcl($sources, false, $this->getUser());
        return $this->serialize($sources, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function restoreAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $sources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allByIds($ids);
        $sources = \Component\Users\Service\AclService::instance()->filterByAcl($sources, true, $this->getUser());
        $items = [];
        foreach ($sources as $source) {
            $items[] = \Component\TrafficSources\Service\TrafficSourceService::instance()->makeActive($source);
        }
        return $this->serialize($items, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function availableParametersAction()
    {
        return \Traffic\Repository\ParameterRepository::getAvailableParameters();
    }
    public function parameterAliasesAction()
    {
        $ts = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->find($this->getParam("ts_id"));
        if (!$this->isViewAllowed($ts)) {
            $this->throwDeny();
        }
        return \Component\TrafficSources\Service\TrafficSourceService::instance()->getParameterAliases($ts);
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $trafficSources = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->allByIds([$id]);
        $trafficSources = \Component\Users\Service\AclService::instance()->filterByAcl($trafficSources, true, $this->getUser());
        $items = [];
        foreach ($trafficSources as $trafficSource) {
            $items[] = \Component\TrafficSources\Service\TrafficSourceService::instance()->updateNote($trafficSource, $note);
        }
        return $this->serialize($items, new \Component\TrafficSources\Serializer\TrafficSourceSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\TrafficSource::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\TrafficSources\PruneTask\PruneTrafficSources();
        $pruner->deleteAll();
    }
}

?>