<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class ApiKeysRepository extends \Core\Entity\Repository\EntityRepository
{
    private $_cache = NULL;
    public function definition()
    {
        return \Component\Users\Model\ApiKey::definition();
    }
    public function getUserKeyCount($userId)
    {
        $this->_memoize();
        if (isset($this->_cache[$userId])) {
            return $this->_cache[$userId];
        }
        return 0;
    }
    public function allByUser(\Component\Users\Model\User $user)
    {
        return $this->all("user_id = " . \Core\Db\Db::quote($user->getId()));
    }
    public function findByUser(\Component\Users\Model\User $user, $id)
    {
        $where = "id = " . \Core\Db\Db::quote($id);
        $where .= " AND user_id = " . \Core\Db\Db::quote($user->getId());
        $key = $this->findFirst($where);
        if (empty($key)) {
            throw new \Core\Exceptions\NotFoundError("Key " . $id . " not found");
        }
        return $key;
    }
    private function _memoize()
    {
        if (!isset($this->_cache)) {
            $this->_cache = $this->_getRows();
        }
    }
    public function _getRows()
    {
        $select = "user_id, count(id) as keyCount";
        $groupBy = "user_id";
        $where = NULL;
        $order = NULL;
        $limit = NULL;
        $offset = NULL;
        $rows = \Core\Db\DataRepository::instance()->rawRows($this->definition(), $select, $where, $order, $limit, $offset, $groupBy);
        $dic = [];
        foreach ($rows as $row) {
            $dic[$row["user_id"]] = $row["keyCount"];
        }
        return $dic;
    }
}

?>