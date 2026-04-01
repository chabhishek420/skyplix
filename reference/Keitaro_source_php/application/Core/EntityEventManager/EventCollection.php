<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\EntityEventManager;

class EventCollection implements \IteratorAggregate
{
    private $_groupedEvents = [];
    public function add($eventName, $entityName, $entityId)
    {
        if (empty($entityName)) {
            throw new \Exception("entityName is empty");
        }
        if (empty($entityId)) {
            throw new \Exception("\$entityId is empty");
        }
        if (is_object($entityId)) {
            throw new \Exception("\$entityId must be and ID");
        }
        if (empty($this->_groupedEvents[$entityName])) {
            $this->_groupedEvents[$entityName] = [Event::UPDATE => [], Event::DELETE => []];
        }
        $this->_groupedEvents[$entityName][$eventName][] = $entityId;
        $this->_groupedEvents[$entityName][$eventName] = array_unique($this->_groupedEvents[$entityName][$eventName]);
    }
    public function groupedEvents()
    {
        return $this->_groupedEvents;
    }
    public function getIterator()
    {
        return new \ArrayIterator($this->_groupedEvents);
    }
    public function flush()
    {
        $this->_groupedEvents = [];
    }
}

?>