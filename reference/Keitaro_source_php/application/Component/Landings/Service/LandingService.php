<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Service;

class LandingService extends \Core\Entity\Service\EntityService
{
    use \Component\Landings\Mixin\ActionableResourceTrait;
    public function definition()
    {
        return \Traffic\Model\Landing::definition();
    }
    public function create($data)
    {
        return $this->_createResource($data);
    }
    public function update(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        return $this->_updateResource($entity, $data);
    }
    public function archive(\Core\Entity\Model\EntityModelInterface $entity)
    {
        self::archive($entity);
        StreamLandingAssociationService::instance()->deleteByLanding($entity);
    }
    public function delete(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->removeFolder($entity);
        return self::delete($entity);
    }
}

?>