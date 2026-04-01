<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Offers\Service;

class StreamOfferAssociationService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Traffic\Model\StreamOfferAssociation::definition();
    }
    public function createAssoc($data)
    {
        if (empty($data["state"])) {
            $data["state"] = \Core\Entity\State::ACTIVE;
        }
        return self::create($data);
    }
    public function assign(\Traffic\Model\BaseStream $stream, $items)
    {
        $ids = [];
        $associations = [];
        if (isset($items) && count($items)) {
            foreach ($items as $data) {
                if (!empty($data["offer_id"])) {
                    $where = ["offer_id = " . \Core\Db\Db::quote($data["offer_id"]), "stream_id = " . \Core\Db\Db::quote($stream->getId())];
                    $assoc = \Component\Streams\Repository\StreamOfferAssociationRepository::instance()->findFirst(implode(" AND ", $where));
                    if (!isset($assoc)) {
                        $data["stream_id"] = $stream->getId();
                        $assoc = $this->createAssoc($data);
                    } else {
                        $this->update($assoc, $data);
                    }
                    $associations[] = $assoc;
                    $ids[] = $assoc->getId();
                }
            }
        }
        $this->deleteByStream($stream, $ids);
        \Component\Streams\Service\StreamService::instance()->updateCache($stream);
        return $associations;
    }
    public function deleteByStream(\Traffic\Model\BaseStream $stream, $exclude = [])
    {
        $where = "stream_id = " . \Core\Db\Db::quote($stream->getId());
        if (!empty($exclude)) {
            $where .= " AND id NOT IN (" . implode(",", $exclude) . ")";
        }
        StreamOfferAssociationService::instance()->deleteMany($where);
    }
    public function deleteByOffer(\Core\Entity\Model\EntityModelInterface $offer, $exclude = [])
    {
        $where = "offer_id = " . \Core\Db\Db::quote($offer->getId());
        if (!empty($exclude)) {
            $where .= " AND id NOT IN (" . implode(",", $exclude) . ")";
        }
        StreamOfferAssociationService::instance()->deleteMany($where);
    }
}

?>