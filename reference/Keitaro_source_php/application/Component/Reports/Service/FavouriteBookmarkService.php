<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Reports\Service;

class FavouriteBookmarkService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Reports\Model\FavouriteBookmark::definition();
    }
    public function createForUser(\Component\Users\Model\User $user, $params)
    {
        $params["user_id"] = $user->getId();
        return $this->create($params);
    }
}

?>