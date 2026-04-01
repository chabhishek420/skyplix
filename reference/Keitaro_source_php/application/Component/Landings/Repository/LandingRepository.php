<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Repository;

class LandingRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Traffic\Model\Landing::definition();
    }
    public function listAsOptions($models)
    {
        return \Core\Entity\ListOptions\Builder::build($this->definition(), $models, []);
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
        $groupTableName = \Component\Groups\Model\Group::getTableName();
        $joins[] = "LEFT JOIN " . $groupTableName . " as groups ON t.group_id = groups.id";
        $select = "t.*, groups.name AS `group`";
        return $this->all($where, $order, $limit, $offset, $select, $groupBy, $joins);
    }
}

?>