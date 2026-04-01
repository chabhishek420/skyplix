<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\Service;

abstract class EntityService extends \Traffic\Service\AbstractService
{
    const COPY_OF = "Copy of ";
    public abstract function definition();
    public function build($data)
    {
        $timeStamps = ["created_at", "updated_at"];
        foreach ($timeStamps as $field) {
            if (empty($data[$field]) && $this->definition()->hasField($field)) {
                $data[$field] = new \DateTime();
            }
        }
        if (empty($data["state"]) && $this->definition()->hasField("state")) {
            $data["state"] = \Core\Entity\State::ACTIVE;
        }
        return \Core\Db\DataService::instance()->build($this->definition(), $data);
    }
    public function restore($rawData)
    {
        return \Core\Db\DataService::instance()->restore($this->definition(), $rawData);
    }
    public function serialize(\Core\Entity\Model\EntityModelInterface $entity)
    {
        return DataConverterService::instance()->prepareForMysql($this->definition()->fields(), $entity->getData());
    }
    public function create($data)
    {
        $data = $this->_cleanData($data);
        $entity = $this->build($data);
        $entity = \Core\Db\DataService::instance()->create($this->definition(), $entity);
        $this->updateCache($entity);
        return $entity;
    }
    public function save(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $entity = \Core\Db\DataService::instance()->save($this->definition(), $entity);
        $this->updateCache($entity);
        return $entity;
    }
    public function replaceEntityFields(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        foreach ($data as $field => $value) {
            if ($this->definition()->hasField($field)) {
                $entity->set($field, $value);
            }
        }
        return $entity;
    }
    public function update(\Core\Entity\Model\EntityModelInterface $entity, $data)
    {
        $entity = $this->replaceEntityFields($entity, $data);
        if ($this->definition()->hasField("updated_at")) {
            $entity->set("updated_at", new \DateTime());
        }
        return $this->save($entity);
    }
    public function updateMany($where, $data)
    {
        foreach ($this->definition()->repository()->all($where) as $entity) {
            $this->update($entity, $data);
        }
    }
    public function delete(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $result = \Core\Db\DataService::instance()->delete($this->definition(), $entity);
        $this->deleteCache($entity);
        $this->deleteAclRule($entity);
        return $result;
    }
    public function deleteMany($where)
    {
        foreach ($this->definition()->repository()->all($where) as $entity) {
            $this->delete($entity);
        }
    }
    public function directDeleteAll($where)
    {
        if (\Core\EntityEventManager\Service\EntityEventService::instance()->getHandlers($this->definition()->entityName())) {
            throw new \Exception("Entity " . $this->definition()->entityName() . " cannot be deleted with directDeleteAll. You must delete it one by one.");
        }
        return \Core\Db\DataService::instance()->deleteAll($this->definition(), $where);
    }
    public function archive(\Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->update($entity, ["state" => \Core\Entity\State::DELETED]);
        return $entity;
    }
    public function makeActive(\Core\Entity\Model\EntityModelInterface $entity)
    {
        return $this->update($entity, ["state" => \Core\Entity\State::ACTIVE]);
    }
    public function cloneEntity(\Core\Entity\Model\EntityModelInterface $entity, $replaceFields = [])
    {
        $data = $entity->getData();
        unset($data["id"]);
        $newEntity = $this->build($data);
        foreach ($replaceFields as $field => $value) {
            $newEntity->set($field, $value);
        }
        $newEntity = \Core\Db\DataService::instance()->create($this->definition(), $newEntity);
        $this->updateCache($newEntity);
        return $newEntity;
    }
    public function updateNote(\Core\Entity\Model\EntityModelInterface $entity, $note)
    {
        return $this->update($entity, ["notes" => $note]);
    }
    public function disable(\Core\Entity\Model\EntityModelInterface $entity)
    {
        return $this->update($entity, ["state" => \Core\Entity\State::DISABLED]);
    }
    public function updateCache(\Core\Entity\Model\EntityModelInterface $entity)
    {
        \Core\EntityEventManager\Service\EntityEventService::instance()->add(\Core\EntityEventManager\Event::UPDATE, $this->definition()->entityName(), $entity->getId());
        return $entity;
    }
    public function deleteCache(\Core\Entity\Model\EntityModelInterface $entity)
    {
        \Core\EntityEventManager\Service\EntityEventService::instance()->add(\Core\EntityEventManager\Event::DELETE, $this->definition()->entityName(), $entity->getId());
        return $entity;
    }
    public function deleteAclRule(\Core\Entity\Model\EntityModelInterface $entity)
    {
        if ($this->definition()->aclKey()) {
            \Component\Users\Service\AclService::instance()->onEntityDelete($this->definition()->aclKey(), $entity->getId());
        }
    }
    private function _cleanData($data)
    {
        foreach ($data as $field => $value) {
            if (!$this->definition()->hasField($field)) {
                unset($data[$field]);
            }
        }
        return $data;
    }
}

?>