<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Repository;

class FavouriteReportRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Reports\Model\FavouriteBookmark::definition();
    }
    public function allByUser(\Component\Users\Model\User $user)
    {
        $where = "user_id = " . \Core\Db\Db::quote($user->getId());
        return $this->all($where, "name");
    }
    public function findByUser(\Component\Users\Model\User $user, $id)
    {
        $where = "user_id = " . \Core\Db\Db::quote($user->getId());
        $where .= " AND id = " . (int) $id;
        $bookmark = $this->findFirst($where);
        if (empty($bookmark)) {
            throw new \Core\Exceptions\NotFoundError("Report is not found");
        }
        return $bookmark;
    }
}

?>