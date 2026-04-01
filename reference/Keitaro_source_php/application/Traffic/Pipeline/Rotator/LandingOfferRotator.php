<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions;

class LandingOfferRotator
{
    private $_bindingEntityType = NULL;
    private $_repository = NULL;
    private $_campaign = NULL;
    private $_logEntry = NULL;
    private $_rawClick = NULL;
    private $_bindEnabled = NULL;
    private $_associationField = NULL;
    private $_entityBindingService = NULL;
    const _ASSOCIATION_FIELD_DIC = NULL;
    public function __construct($bindingEntityType, \Traffic\Repository\AbstractBaseRepository $repository, \Traffic\Model\Campaign $campaign, \Traffic\Logging\TrafficLogEntry $logEntry, \Traffic\RawClick $rawClick)
    {
        if (empty(_ASSOCIATION_FIELD_DIC[$bindingEntityType])) {
            throw new \Exception("Unexptected entity type " . $bindingEntityType);
        }
        $this->_bindingEntityType = $bindingEntityType;
        $this->_repository = $repository;
        $this->_campaign = $campaign;
        $this->_logEntry = $logEntry;
        $this->_rawClick = $rawClick;
        $this->_associationField = _ASSOCIATION_FIELD_DIC[$bindingEntityType];
        $this->_bindEnabled = $this->_getBindEnabled();
        $this->_entityBindingService = new \Traffic\Pipeline\Service\EntityBindingService($this->_rawClick, $this->_campaign, $this->_logEntry);
    }
    private function _getBindEnabled($_getBindEnabled)
    {
        if ($this->_bindingEntityType === \Traffic\Pipeline\Service\EntityBindingService::TYPE_LANDING_BINDING && $this->_campaign->isBindVisitorsLandingEnabled()) {
            return true;
        }
        if ($this->_bindingEntityType === \Traffic\Pipeline\Service\EntityBindingService::TYPE_OFFER_BINDING && $this->_campaign->isBindVisitorsOfferEnabled()) {
            return true;
        }
        return false;
    }
    private function _getRepository()
    {
        return $this->_repository;
    }
    protected function _isBindVisitorsEnabled()
    {
        return $this->_bindEnabled;
    }
    private function _getEntityFromAssociation(\Core\Entity\Model\EntityModelInterface $item)
    {
        return $this->_getRepository()->findCached($item->get($this->_associationField));
    }
    public function getRandom(\Traffic\Request\ServerRequest $serverRequest, $associations, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        if (empty($associations)) {
            \Traffic\Logging\Service\LoggerService::instance()->debug("[Rotator] no more associations");
        } else {
            $entity = NULL;
            $association = NULL;
            if ($this->_isBindVisitorsEnabled()) {
                $entityId = $this->_findBoundEntityId($serverRequest);
                \Traffic\Logging\Service\LoggerService::instance()->debug("[Rotator] bound to " . $entityId);
                $association = $this->_findAssociationByEntityId($associations, $entityId);
            }
            if (is_null($association)) {
                $association = $this->_rollDice($associations);
                if (is_null($association)) {
                    \Traffic\Logging\Service\LoggerService::instance()->debug("[Rotator] all associations with share 0 or disabled");
                    return NULL;
                }
            }
            \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
                return "[Rotator] Got association: " . json_encode($association->getData());
            });
            $entity = $this->_getEntityFromAssociation($association);
            if (!$this->_isEntityOk($entity)) {
                \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
                    return "[Rotator] Entity is not ok: " . json_encode($entity->getData());
                });
                $associations = $this->_deleteAssociation($associations, $association);
                return $this->getRandom($serverRequest, $associations, $logEntry);
            }
            \Traffic\Logging\Service\LoggerService::instance()->debug(function () {
                return "[Rotator] Got entity: " . json_encode($entity->getData());
            });
            return $entity;
        }
    }
    private function _rollDice($items)
    {
        if (!is_array($items) || empty($items)) {
            return NULL;
        }
        shuffle($items);
        $totalWeight = 0;
        $selected = 0;
        foreach ($items as $i => $item) {
            $weight = $item->getShare();
            $rand = mt_rand(0, $totalWeight + $weight);
            if ($totalWeight <= $rand) {
                $selected = $i;
            }
            $totalWeight += $weight;
        }
        $selectedItem = $items[$selected];
        if ($selectedItem && $selectedItem->getShare() !== 0 && $selectedItem->getState() !== \Core\Entity\State::DISABLED) {
            return $selectedItem;
        }
        unset($items[$selected]);
        return $this->_rollDice($items);
    }
    private function _deleteAssociation($associations, $association)
    {
        $index = array_search($association, $associations);
        unset($associations[$index]);
        return $associations;
    }
    private function _isEntityOk($entity)
    {
        return $entity && $entity->getState() == \Core\Entity\State::ACTIVE;
    }
    private function _findBoundEntityId(\Traffic\Request\ServerRequest $serverRequest)
    {
        return $this->_entityBindingService->findBoundEntity($serverRequest, $this->_bindingEntityType);
    }
    private function _findAssociationByEntityId($associations, $entityId)
    {
        if (empty($associations)) {
            return NULL;
        }
        if (empty($entityId)) {
            return NULL;
        }
        foreach ($associations as $association) {
            if ($association->get($this->_associationField) == $entityId) {
                return $association;
            }
        }
        return NULL;
    }
}

?>