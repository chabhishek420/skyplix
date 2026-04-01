<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\Repository;

abstract class EntityRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_memoized = [];
    public abstract function definition();
    public function all($where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $select = NULL, $groupBy = NULL, $joins = NULL)
    {
        return \Core\Db\DataRepository::instance()->all($this->definition(), $where, $order, $limit, $offset, $select, $groupBy, $joins);
    }
    public function count($where = NULL)
    {
        return \Core\Db\DataRepository::instance()->count($this->definition(), $where);
    }
    public function find($id)
    {
        return \Core\Db\DataRepository::instance()->find($this->definition(), $id);
    }
    public function findLast($where = NULL)
    {
        return \Core\Db\DataRepository::instance()->findLast($this->definition(), $where);
    }
    public function findFirst($where = NULL, $order = NULL)
    {
        return \Core\Db\DataRepository::instance()->findFirst($this->definition(), $where, $order);
    }
    public function allByIds($ids)
    {
        return \Core\Db\DataRepository::instance()->findByIds($this->definition(), $ids);
    }
    public function getAllIds()
    {
        return \Core\Db\DataRepository::instance()->getAllIds($this->definition());
    }
    public function exists($where)
    {
        return \Core\Db\DataRepository::instance()->exists($this->definition(), $where);
    }
    public function rawRows($select = NULL, $where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $groupBy = NULL)
    {
        return \Core\Db\DataRepository::instance()->rawRows($this->definition(), $select, $where, $order, $limit, $offset, $groupBy);
    }
    public function pluck($where = NULL, $select = NULL, $order = NULL)
    {
        return \Core\Db\DataRepository::instance()->pluck($this->definition(), $where, $select, $order);
    }
    public function getName($id)
    {
        if (empty($id)) {
            return NULL;
        }
        $key = $this->definition()->tableName() . $id;
        if (!isset($this->_memoized[$key])) {
            $modelName = \Core\Db\DataRepository::instance()->getOne($this->definition(), "name", "id = " . \Core\Db\Db::quote($id));
            $this->_memoized[$key] = $modelName;
        }
        return $this->_memoized[$key];
    }
    public function allDeletedBefore(\DateTime $date)
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $where .= " AND updated_at < " . \Core\Db\Db::quote($date->format(\Core\Model\AbstractModel::DATETIME_FORMAT));
        return $this->all($where);
    }
    public function allDeleted()
    {
        return $this->all("state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED));
    }
    public function allNotDeleted($where = NULL, $order = NULL, $limit = NULL, $offset = NULL)
    {
        $conditions = [];
        $conditions[] = "state <> " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        if ($where) {
            $conditions[] = $where;
        }
        return $this->all(implode(" AND ", $conditions), $order, $limit, $offset);
    }
    public function allActive($where = NULL, $order = NULL, $limit = NULL, $offset = NULL)
    {
        $conditions = [];
        $conditions[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        if ($where) {
            $conditions[] = $where;
        }
        return $this->all(implode(" AND ", $conditions), $order, $limit, $offset);
    }
    public function findActive($id)
    {
        return $this->findFirst("id = " . \Core\Db\Db::quote($id) . " AND state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE));
    }
    public function countActive($where = NULL)
    {
        $conditions = [];
        $conditions[] = "state = " . \Core\Db\Db::quote(\Core\Entity\State::ACTIVE);
        if ($where) {
            $conditions[] = $where;
        }
        return $this->count(implode(" AND ", $conditions));
    }
    public function allWithStats($params, \Component\Grid\QueryParams\UserParams $userParams)
    {
        $definition = $this->definition();
        $factory = new \Component\EntityGrid\EntityGridFactory();
        return $factory->userParams($userParams)->setEntityDefinition($definition)->params($params)->build();
    }
}

?>