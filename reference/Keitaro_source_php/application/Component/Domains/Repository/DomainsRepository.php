<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\Repository;

class DomainsRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\Domain::definition();
    }
    public function listAsOptions($listAsOptions, $basePath, $modelList = true, $addDefault)
    {
        $items = [];
        foreach ($modelList as $model) {
            $object = ["value" => $model->getId(), "name" => \Component\Domains\Service\DomainService::instance()->urlWithBasePath($model, $basePath)];
            $items[] = $object;
        }
        if ($addDefault) {
            array_unshift($items, ["value" => NULL, "name" => \Core\Locale\LocaleService::instance()->t("domains.default_domain")]);
        }
        return $items;
    }
    public function allActiveAndChecked()
    {
        $where = "network_status = " . \Core\Db\Db::quote(\Traffic\Model\Domain::NETWORK_STATUS_ACTIVE);
        $where .= " AND state  = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->getAllAvailableDomains($where);
    }
    public function allActiveAndAwaitingSSL($limit = NULL)
    {
        $where = "ssl_status = " . \Core\Db\Db::quote(\Traffic\Model\Domain::SSL_STATUS_AWAITING_SSL);
        $where .= " AND state  = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->getAllAvailableDomains($where, $limit);
    }
    public function activeCount()
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->count($where);
    }
    public function getAllActiveIds()
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->pluck($where, $this->definition()->primaryKey());
    }
    public function getAllByIds($ids)
    {
        return $this->getAllByIds($ids);
    }
    public function allActiveByLicenseType()
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        return $this->getAllAvailableDomains($where);
    }
    public function getAllByNames($domainNames)
    {
        if (!count($domainNames)) {
            return [];
        }
        $domainNames = array_map(function ($name) {
            return \Core\Db\Db::quote($name);
        }, $domainNames);
        $domainNames = implode(",", $domainNames);
        $where = "name IN (" . $domainNames . ")";
        return $this->all($where);
    }
    public function getAllAvailableDomains($where, $limit = NULL)
    {
        if (\Core\Application\FeatureService::instance()->isBasic()) {
            return $this->all($where, "id", 1);
        }
        return $this->all($where, NULL, $limit);
    }
}

?>