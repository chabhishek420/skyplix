<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Repository;

class OfferRepository extends \Core\Entity\Repository\EntityRepository
{
    protected $_costTypes = NULL;
    public function definition()
    {
        return \Traffic\Model\Offer::definition();
    }
    public function listAsOptions($models)
    {
        return \Core\Entity\ListOptions\Builder::build($this->definition(), $models, []);
    }
    public function getCostTypes()
    {
        $result = [];
        foreach ($this->_costTypes as $mode) {
            $result[] = ["value" => $mode, "name" => \Core\Locale\LocaleService::t("offers.cost_types." . $mode)];
        }
        return $result;
    }
    public function allNotDeletedWithRelations($order = "t.name", $limit = NULL, $offset = NULL)
    {
        $where = "t.state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        return $this->allWithRelations($where, $order, $limit, $offset);
    }
    public function allWithRelations($where = NULL, $order = "t.name", $limit = NULL, $offset = NULL)
    {
        $groupBy = "t.id";
        $joins = [];
        $affTableName = \Traffic\Model\AffiliateNetwork::getTableName();
        $joins[] = "LEFT JOIN " . $affTableName . " AS networks ON t.affiliate_network_id = networks.id";
        $groupTableName = \Component\Groups\Model\Group::getTableName();
        $joins[] = "LEFT JOIN " . $groupTableName . " as groups ON t.group_id = groups.id";
        $select = "t.*, networks.name AS affiliate_network, groups.name AS `group`";
        return $this->all($where, $order, $limit, $offset, $select, $groupBy, $joins);
    }
}

?>