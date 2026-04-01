<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Model;

abstract class AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected $_validationEnabled = true;
    protected $_data = [];
    protected static $_tableName = NULL;
    protected static $_primaryKey = "id";
    protected static $_aclKey = NULL;
    protected static $_cacheKey = NULL;
    protected static $_entityName = NULL;
    protected static $_fields = NULL;
    protected static $_definition = NULL;
    const DATETIME_FORMAT = "Y-m-d H:i:s";
    const DATE_FORMAT = "Y-m-d";
    public function __construct($data = NULL)
    {
        if (isset($data) && is_array($data)) {
            $this->setData($data);
        }
    }
    public static function definition()
    {
        if (!isset(self::$_definition[])) {
            self::$_definition[] = new \Core\Entity\Definition\EntityDefinition(["class_name" => self::getClassName(), "primary_key" => self::$_primaryKey, "acl_key" => self::aclKey(), "cache_key" => self::cacheKey(), "table_name" => self::getTableName(), "entity_name" => self::entityName(), "serializer" => function () {
                return self::serializer();
            }, "repository" => function () {
                return self::repository();
            }, "service" => function () {
                return self::service();
            }, "report_definition" => function () {
                return self::reportDefinition();
            }, "validator" => function () {
                return self::validator();
            }, "fields" => self::$_fields]);
        }
        return self::$_definition[];
    }
    public static function serializer()
    {
        return NULL;
    }
    public static function validator()
    {
        return NULL;
    }
    public static function repository()
    {
        return NULL;
    }
    public static function service()
    {
        return NULL;
    }
    public static function reportDefinition()
    {
        return NULL;
    }
    public static function aclKey()
    {
        return self::$_aclKey;
    }
    public static function entityName()
    {
        return self::$_entityName;
    }
    public static function cacheKey()
    {
        return self::$_cacheKey;
    }
    public static function hasStateField()
    {
        return !empty(self::$_fields["state"]);
    }
    public static function getTableName()
    {
        return \Core\Db\Db::getPrefix() . self::$_tableName;
    }
    public static function getPrimaryKey()
    {
        return self::$_primaryKey;
    }
    public static function getFields()
    {
        return self::$_fields;
    }
    public static function getClassName()
    {
        return get_called_class();
    }
    public static function exists($where = NULL)
    {
        return \Core\Db\DataRepository::instance()->exists(self::definition(), $where);
    }
    public static function find($id)
    {
        return \Core\Db\DataRepository::instance()->find(self::definition(), $id);
    }
    public static function all($where = NULL, $order = NULL, $limit = NULL, $offset = NULL)
    {
        return \Core\Db\DataRepository::instance()->all(self::definition(), $where, $order, $limit, $offset);
    }
    public static function fetchAll($where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $select = NULL)
    {
        return \Core\Db\DataRepository::instance()->all(self::definition(), $where, $order, $limit, $offset, $select);
    }
    public static function fetchRows($select = NULL, $where = NULL, $order = NULL, $limit = NULL, $offset = NULL, $groupBy = NULL)
    {
        return \Core\Db\DataRepository::instance()->rawRows(self::definition(), $select, $where, $order, $limit, $offset, $groupBy);
    }
    public static function findFirst($where = NULL, $order = NULL)
    {
        return \Core\Db\DataRepository::instance()->findFirst(self::definition(), $where, $order);
    }
    public static function fetchRow($select = "*", $where = NULL, $order = NULL, $group = NULL)
    {
        return \Core\Db\DataRepository::instance()->fetchRow(self::definition(), $select, $where, $order, $group);
    }
    public static function findLast($where = NULL)
    {
        return \Core\Db\DataRepository::instance()->findLast(self::definition(), $where);
    }
    public static function count($where = NULL)
    {
        return \Core\Db\DataRepository::instance()->count(self::definition(), $where);
    }
    public static function build($data)
    {
        $className = self::getClassName();
        return new $className($data);
    }
    public static function create($data)
    {
        $entity = new $this($data);
        return \Core\Db\DataService::instance()->create(self::definition(), $entity);
    }
    public function setId($id)
    {
        $this->_data[self::$_primaryKey] = $id;
        return $this;
    }
    public function setData($data)
    {
        foreach ($data as $field => $value) {
            if (isset(self::$_fields[$field])) {
                $this->_data[$field] = $value;
            } else {
                throw new \Core\Validator\ValidationError("No field '" . $field . "' in ");
            }
        }
        return $this;
    }
    public function restoreData($data)
    {
        $this->_data = \Core\Entity\Service\DataConverterService::instance()->restoreFromMysql(self::$_fields, $data);
    }
    public function getData()
    {
        return $this->_data;
    }
    public function hasField($key)
    {
        return isset(self::$_fields[$key]);
    }
    public function get($key)
    {
        if (!$this->hasField($key)) {
            $message = "Model " . get_class($this) . " does not have field " . $key;
            throw new \Exception($message);
        }
        if (isset($this->_data[$key])) {
            return $this->_data[$key];
        }
        return NULL;
    }
    public function set($key, $value)
    {
        if (!$this->hasField($key)) {
            throw new \Core\Validator\ValidationError("No field '" . $key . "' in ");
        }
        $this->_data[$key] = $value;
        return $this;
    }
    public function unset_value($key)
    {
        unset($this->_data[$key]);
        return $this;
    }
    public function getId()
    {
        return $this->get(self::getPrimaryKey());
    }
    public function reload()
    {
        $data = \Core\Db\DataRepository::instance()->findRaw(self::definition(), $this->getId());
        $this->_data = [];
        $this->restoreData($data);
        return $this;
    }
    public function getModelTableName()
    {
        return self::getTableName();
    }
    public function save()
    {
        if (!$this->getId()) {
            return \Core\Db\DataService::instance()->create(self::definition(), $this);
        }
        return \Core\Db\DataService::instance()->save(self::definition(), $this);
    }
    public function delete()
    {
        return \Core\Db\DataService::instance()->delete(self::definition(), $this);
    }
    protected function getCoercions()
    {
        return self::$_fields;
    }
    public function serialize()
    {
        return \Core\Entity\Service\DataConverterService::instance()->prepareForMysql(self::$_fields, $this->getData());
    }
}

?>