<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class FavouriteStreamRepository extends \Core\Entity\Repository\EntityRepository
{
    public function definition()
    {
        return \Component\Streams\Model\FavouriteStream::definition();
    }
    public function getFavouriteStreams(\Component\Users\Model\User $user)
    {
        $ids = $this->pluck("user_id = " . $user->getId(), "stream_id");
        if (!count($ids)) {
            return [];
        }
        return StreamRepository::instance()->getStreams("id IN (" . implode(",", $ids) . ")", "name");
    }
}

?>