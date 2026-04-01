<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Repository;

class PreloadedResourceRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_filters = NULL;
    private $_triggers = NULL;
    private $_unreadCount = NULL;
    private $_events = NULL;
    private $_landingAssociations = NULL;
    private $_offerAssociations = NULL;
    private $_offers = NULL;
    public function preloadForStreams($streams)
    {
        if (!count($streams)) {
            return NULL;
        }
        $ids = array_map(function (\Traffic\Model\BaseStream $stream) {
            return $stream->getId();
        }, $streams);
        $this->_filters = $this->_preloadFilters($ids);
        $this->_triggers = $this->_preloadTriggers($ids);
        $this->_unreadCount = $this->_preloadUnreadEventCount($ids);
        $this->_events = $this->_preloadEvents($ids);
        $this->_landingAssociations = $this->_preloadLandingAssociations($ids);
        $this->_offerAssociations = $this->_preloadOfferAssociations($ids);
        $this->_offers = $this->_preloadOffers();
    }
    private function _preloadFilters($ids)
    {
        $result = [];
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $rows = \Component\StreamFilters\Repository\StreamFilterRepository::instance()->all($where, "id desc");
        foreach ($rows as $streamFilter) {
            $result[$streamFilter->getStreamId()][] = $streamFilter;
        }
        return $result;
    }
    private function _preloadUnreadEventCount($ids)
    {
        $select = "stream_id, count(id) as cnt";
        $groupBy = "stream_id";
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $where .= " AND state = " . \Core\Db\Db::quote(\Component\Streams\Model\StreamEvent::UNREAD);
        $rows = StreamEventsRepository::instance()->rawRows($select, $where, NULL, NULL, NULL, $groupBy);
        $result = [];
        foreach ($rows as $row) {
            $result[$row["stream_id"]] = $row["cnt"];
        }
        return $result;
    }
    private function _preloadTriggers($ids)
    {
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $rows = \Component\Triggers\Repository\TriggersRepository::instance()->all($where);
        $result = [];
        foreach ($rows as $triggerAssociation) {
            if (!isset($result[$triggerAssociation->getStreamId()])) {
                $result[$triggerAssociation->getStreamId()] = [];
            }
            $result[$triggerAssociation->getStreamId()][] = $triggerAssociation;
        }
        return $result;
    }
    private function _preloadEvents($ids)
    {
        $select = "stream_id, COUNT(*) as cnt";
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $groupBy = "stream_id";
        $rows = StreamEventsRepository::instance()->rawRows($select, $where, NULL, NULL, NULL, $groupBy);
        $result = [];
        foreach ($rows as $row) {
            $result[$row["stream_id"]] = $row["cnt"];
        }
        return $result;
    }
    private function _preloadLandingAssociations($ids)
    {
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $rows = StreamLandingAssociationRepository::instance()->all($where, "id");
        $result = [];
        foreach ($rows as $association) {
            if (!isset($result[$association->get("stream_id")])) {
                $result[$association->get("stream_id")] = [];
            }
            $result[$association->get("stream_id")][] = $association;
        }
        return $result;
    }
    private function _preloadOfferAssociations($ids)
    {
        $where = "stream_id IN (" . implode(",", $ids) . ")";
        $rows = StreamOfferAssociationRepository::instance()->all($where, "id");
        $result = [];
        foreach ($rows as $association) {
            if (!isset($result[$association->get("stream_id")])) {
                $result[$association->get("stream_id")] = [];
            }
            $result[$association->get("stream_id")][] = $association;
        }
        return $result;
    }
    private function _preloadOffers()
    {
        if (empty($this->_offerAssociations)) {
            return NULL;
        }
        $ids = [];
        foreach ($this->_offerAssociations as $items) {
            foreach ($items as $assoc) {
                $ids[] = $assoc->get("offer_id");
            }
        }
        $offers = \Component\Offers\Repository\OfferRepository::instance()->all("id IN (" . implode(",", $ids) . ")");
        $result = [];
        foreach ($offers as $offer) {
            $result[$offer->getId()] = $offer;
        }
        return $result;
    }
    public function getFilters(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_filters[$stream->getId()]) ? $this->_filters[$stream->getId()] : [];
    }
    public function getTriggers(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_triggers[$stream->getId()]) ? $this->_triggers[$stream->getId()] : [];
    }
    public function getLandingAssociations(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_landingAssociations[$stream->getId()]) ? $this->_landingAssociations[$stream->getId()] : [];
    }
    public function getOfferAssociations(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_offerAssociations[$stream->getId()]) ? $this->_offerAssociations[$stream->getId()] : [];
    }
    public function getEvents(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_events[$stream->getId()]) ? $this->_events[$stream->getId()] : [];
    }
    public function unreadCountForStream(\Traffic\Model\BaseStream $stream)
    {
        return isset($this->_unreadCount[$stream->getId()]) ? $this->_unreadCount[$stream->getId()] : 0;
    }
    public function getOffer($id)
    {
        return isset($this->_offers[$id]) ? $this->_offers[$id] : NULL;
    }
}

?>