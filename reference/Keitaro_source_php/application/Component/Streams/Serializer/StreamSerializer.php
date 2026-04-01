<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Serializer;

class StreamSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    private $_withEvents = NULL;
    private $_withOffersCapacity = NULL;
    public function __construct($withEvents = false, $withOffersCapacity = false)
    {
        $this->_withEvents = $withEvents;
        $this->_withOffersCapacity = $withOffersCapacity;
    }
    public function prepare($payload)
    {
        if (is_array($payload)) {
            $streams = $payload;
        } else {
            $streams = [$payload];
        }
        \Component\Streams\Repository\PreloadedResourceRepository::instance()->preloadForStreams($streams);
    }
    public function extra($obj, $data)
    {
        foreach (\Component\Migrations\Migrator7\TdsMigrator7::getKeitaro6Fields() as $field) {
            unset($data[$field]);
        }
        if ($this->_withEvents) {
            $data["unread_events_count"] = \Component\Streams\Repository\PreloadedResourceRepository::instance()->unreadCountForStream($obj);
        }
        $data = $this->_flatTimestamps($data);
        $this->_addAssociation($obj, $data);
        unset($data["landing_id"]);
        unset($data["offer_id"]);
        unset($data["landing_id"]);
        unset($data["offer_id"]);
        unset($data["status"]);
        unset($data["updated_at"]);
        $this->_addAssociation($obj, $data);
        return $data;
    }
    protected function _addAssociation($obj, $data)
    {
        $filters = \Component\Streams\Repository\PreloadedResourceRepository::instance()->getFilters($obj);
        $triggers = \Component\Streams\Repository\PreloadedResourceRepository::instance()->getTriggers($obj);
        $landings = \Component\Streams\Repository\PreloadedResourceRepository::instance()->getLandingAssociations($obj);
        $offers = \Component\Streams\Repository\PreloadedResourceRepository::instance()->getOfferAssociations($obj);
        $data["filters"] = \Core\Json\SerializerFactory::serialize($filters, new \Component\StreamFilters\Serializer\StreamFilterSerializer());
        $data["triggers"] = \Core\Json\SerializerFactory::serialize($triggers, new StreamTriggerSerializer());
        $data["landings"] = \Core\Json\SerializerFactory::serialize($landings, new \Component\Landings\Serializer\StreamLandingAssociationSerializer());
        $data["offers"] = \Core\Json\SerializerFactory::serialize($offers, new \Component\Offers\Serializer\StreamOfferAssociationSerializer($this->_withOffersCapacity));
    }
}

?>