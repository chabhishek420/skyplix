<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Service;

class FavouriteStreamService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\Streams\Model\FavouriteStream::definition();
    }
    public function addStream(\Component\Users\Model\User $user, \Traffic\Model\Stream $stream)
    {
        try {
            $this->create(["user_id" => $user->getId(), "stream_id" => $stream->getId()]);
        } catch (\ADODB_Exception $e) {
        }
    }
    public function removeStream(\Component\Users\Model\User $user, \Traffic\Model\Stream $stream)
    {
        $where = "user_id = " . $user->getId() . " AND stream_id=" . $stream->getId();
        $this->deleteMany($where);
    }
}

?>