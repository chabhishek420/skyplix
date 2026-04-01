<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Db;

class DataService extends \Traffic\Service\AbstractService
{
    public function build(\Core\Entity\Definition\EntityDefinition $definition, $data)
    {
        if (empty($data)) {
            throw new \Core\Application\Exception\Error("Empty data");
        }
        $className = $definition->className();
        $entity = new $className($data);
        return $entity;
    }
    public function restore(\Core\Entity\Definition\EntityDefinition $definition, $rawData)
    {
        $className = $definition->className();
        $data = \Core\Entity\Service\DataConverterService::instance()->restoreFromMysql($definition->fields(), $rawData);
        return new $className($data);
    }
    public function create(\Core\Entity\Definition\EntityDefinition $definition, \Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->validate($definition, $entity);
        $data = $entity->serialize();
        $id = Db::instance()->insert($definition->tableName(), $data);
        $entity->set($definition->primaryKey(), $id);
        return $entity;
    }
    public function save(\Core\Entity\Definition\EntityDefinition $definition, \Core\Entity\Model\EntityModelInterface $entity)
    {
        $this->validate($definition, $entity);
        $data = \Core\Entity\Service\DataConverterService::instance()->prepareForMysql($definition->fields(), $entity->getData());
        $where = "`" . $definition->primaryKey() . "` = " . Db::quote($entity->getId());
        $this->updateMany($definition, $where, $data);
        return $entity;
    }
    public function updateMany(\Core\Entity\Definition\EntityDefinition $definition, $where, $data)
    {
        Db::instance()->update($definition->tableName(), $where, $data);
    }
    public function delete(\Core\Entity\Definition\EntityDefinition $definition, \Core\Entity\Model\EntityModelInterface $entity)
    {
        if (!$entity->get($definition->primaryKey())) {
            throw new \Exception($definition->entityName() . " does not have \$_primaryKey");
        }
        $sql = "DELETE FROM " . $definition->tableName() . " WHERE `" . $definition->primaryKey() . "` = " . DB::quote($entity->get($definition->primaryKey()));
        Db::instance()->execute($sql);
    }
    public function validate(\Core\Entity\Definition\EntityDefinition $definition, \Core\Entity\Model\EntityModelInterface $entity)
    {
        $validator = $definition->validator();
        if (!empty($validator)) {
            $validator->validate($entity->getData());
        }
        return true;
    }
    public function deleteAll(\Core\Entity\Definition\EntityDefinition $definition, $where, $deleteInChunks = false)
    {
        if (empty($where)) {
            throw new \Exception("directDeleteAll must be called with \$where");
        }
        $sql = "DELETE FROM " . $definition->tableName() . " WHERE " . $where;
        if ($deleteInChunks) {
            Db::instance()->executeUntilFinished($sql);
        } else {
            Db::instance()->execute($sql);
        }
    }
}

?>