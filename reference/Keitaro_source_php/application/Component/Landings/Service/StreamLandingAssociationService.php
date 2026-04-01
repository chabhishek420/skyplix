<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Service;

class StreamLandingAssociationService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\StreamLandingAssociation::definition();
    }
    public function assign(\Traffic\Model\BaseStream $stream, $items)
    {
        $ids = [];
        $associations = [];
        if (isset($items) && count($items)) {
            foreach ($items as $data) {
                if (!empty($data["landing_id"])) {
                    if (empty($data["state"])) {
                        $data["state"] = \Core\Entity\State::ACTIVE;
                    }
                    $where = ["landing_id = " . \Core\Db\Db::quote($data["landing_id"]), "stream_id = " . \Core\Db\Db::quote($stream->getId())];
                    $assoc = \Component\Streams\Repository\StreamLandingAssociationRepository::instance()->findFirst(implode(" AND ", $where));
                    if (!isset($assoc)) {
                        $data["stream_id"] = $stream->getId();
                        $assoc = $this->create($data);
                    } else {
                        $assoc = $this->update($assoc, $data);
                    }
                    $associations[] = $assoc;
                    $ids[] = $assoc->getId();
                }
            }
        }
        \Component\Streams\Service\StreamService::instance()->updateCache($stream);
        $this->deleteByStream($stream, $ids, false);
        return $associations;
    }
    public function deleteByStream(\Traffic\Model\BaseStream $stream, $exclude = [])
    {
        $where = "stream_id = " . \Core\Db\Db::quote($stream->getId());
        if (!empty($exclude)) {
            $where .= " AND id NOT IN (" . implode(",", $exclude) . ")";
        }
        $this->deleteMany($where);
    }
    public function deleteByLanding(\Traffic\Model\Landing $landing, $exclude = [])
    {
        $where = "landing_id = " . \Core\Db\Db::quote($landing->getId());
        if (!empty($exclude)) {
            $where .= " AND id NOT IN (" . implode(",", $exclude) . ")";
        }
        $this->deleteMany($where);
    }
}

?>