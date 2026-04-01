<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Controller;

class DomainsController extends \Admin\Controller\BaseController implements \Admin\Controller\EntityControllerInterface
{
    public function indexAction()
    {
        \Component\Domains\Service\DomainService::instance()->cacheBasePath($this->getServerRequest());
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allActiveByLicenseType();
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, false, $this->getUser());
        return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function updateStatusAction()
    {
        $domains = [];
        if ($this->getParam("id")) {
            $id = (int) $this->getParam("id");
            $domains[] = \Component\Domains\Repository\DomainsRepository::instance()->findActive($id);
        }
        if ($this->getParam("ids")) {
            $ids = $this->getParam("ids");
            $domains = \Component\Domains\Repository\DomainsRepository::instance()->allByIds($ids);
        }
        $domains = $this->_findActiveDomainsFilteredByAcl($domains);
        if (empty($domains)) {
            $this->throwNotFound("No ID provided");
        }
        if (1 < count($domains)) {
            \Component\Domains\Service\DomainCheckerService::instance()->prepareMassCheck($domains);
            return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
        }
        $domains = \Component\Domains\Service\DomainCheckerService::instance()->updateDomainsStatus($domains, \Component\Domains\Service\DomainService::instance()->findBasePath($this->getServerRequest()));
        return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function showAction()
    {
        $id = (int) $this->getParam("id");
        $domain = \Component\Domains\Repository\DomainsRepository::instance()->findActive($id);
        if (!$this->isViewAllowed($domain)) {
            $this->throwDeny();
        }
        return $this->serialize($domain, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function listAsOptionsAction($listAsOptionsAction)
    {
        $addDefault = true;
        if ($this->getServerRequest()->hasParam("add_default")) {
            $addDefault = (int) filter_var($this->getParam("add_default"), FILTER_VALIDATE_BOOLEAN);
        }
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allActiveAndChecked();
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, false, $this->getUser());
        $basePath = \Component\Domains\Service\DomainService::instance()->findBasePath($this->getServerRequest());
        return \Component\Domains\Repository\DomainsRepository::instance()->listAsOptions($basePath, $domains, $addDefault);
    }
    public function createAction()
    {
        $this->_checkFeatureAvailable();
        if (!$this->isCreateAllowed(\Traffic\Model\Domain::aclKey())) {
            $this->throwDeny();
        }
        $data = $this->_checkDomainsFeatureAndLicense($this->getPostParams());
        $data = $this->_findOldParams($data);
        $data["network_status"] = \Traffic\Model\Domain::NETWORK_STATUS_VALIDATING;
        $domains = \Component\Domains\Service\DomainService::instance()->createMultiple($data);
        \Component\Users\Service\AclService::instance()->addAuthorPermission($this->getUser(), $domains, false);
        return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function updateAction()
    {
        $id = (int) $this->getParam("id");
        $domain = \Component\Domains\Repository\DomainsRepository::instance()->find($id);
        if (!$this->isEditAllowed($domain)) {
            $this->throwDeny();
        }
        $data = $this->_findOldParams($this->getPostParams());
        unset($data["network_status"]);
        unset($data["is_ssl"]);
        if ($domain->isActive()) {
            unset($data["name"]);
        }
        $domain = \Component\Domains\Service\DomainService::instance()->update($domain, $data);
        return $this->serialize($domain, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function archiveAction()
    {
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allByIds($ids);
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, true, $this->getUser());
        foreach ($domains as $domain) {
            \Component\Domains\Service\DomainService::instance()->archive($domain);
        }
        return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function deletedAction()
    {
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allDeleted();
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, false, $this->getUser());
        return $this->serialize($domains, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function restoreAction()
    {
        $this->_checkFeatureAvailable();
        $ids = $this->getParam("ids");
        if ($this->getParam("id")) {
            $ids = [$this->getParam("id")];
        }
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allByIds($ids);
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, true, $this->getUser());
        $items = [];
        foreach ($domains as $domain) {
            $items[] = \Component\Domains\Service\DomainService::instance()->makeActive($domain);
        }
        return $this->serialize($items, new \Component\Domains\Serializer\DomainSerializer());
    }
    public function cleanArchiveAction()
    {
        if (!$this->isCreateAllowed(\Traffic\Model\Domain::aclKey())) {
            $this->throwDeny();
        }
        $pruner = new \Component\Domains\PruneTask\PruneDomains();
        $pruner->deleteAll();
    }
    public function saveNoteAction()
    {
        $id = $this->getParam("id");
        $note = $this->getParam("note");
        $domains = \Component\Domains\Repository\DomainsRepository::instance()->allByIds([$id]);
        $domains = \Component\Users\Service\AclService::instance()->filterByAcl($domains, true, $this->getUser());
        $items = [];
        foreach ($domains as $domain) {
            $items[] = \Component\Domains\Service\DomainService::instance()->updateNote($domain, $note);
        }
        return $this->serialize($items, new \Component\Domains\Serializer\DomainSerializer());
    }
    private function _checkFeatureAvailable()
    {
        if (\Core\Application\FeatureService::instance()->hasDomainsFeature()) {
            return true;
        }
        $countDomain = \Component\Domains\Repository\DomainsRepository::instance()->activeCount();
        if (0 < $countDomain) {
            throw new \Core\Application\Exception\EditionError();
        }
    }
    private function _checkDomainsFeatureAndLicense($data)
    {
        if (\Core\Application\FeatureService::instance()->hasDomainsFeature()) {
            return $data;
        }
        if (!isset($data["name"])) {
            $data["name"] = "";
        }
        $result = $data;
        $names = explode(",", $data["name"]);
        $result["name"] = $names[0];
        return $result;
    }
    private function _findOldParams($data)
    {
        $result = $data;
        if (!empty($data["redirect"])) {
            unset($result["redirect"]);
            $result["ssl_redirect"] = $data["redirect"] === "https";
        }
        if (!empty($data["is_robots_allowed"])) {
            unset($result["is_robots_allowed"]);
            $result["allow_indexing"] = $data["is_robots_allowed"];
        }
        if (empty($data["is_ssl"]) || $data["is_ssl"]) {
            $result["is_ssl"] = false;
        }
        return $result;
    }
    private function _findActiveDomainsFilteredByAcl($domains)
    {
        return \Component\Users\Service\AclService::instance()->filterByAcl($domains, true, $this->getUser());
    }
}

?>