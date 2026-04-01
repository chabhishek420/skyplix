<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Db;

class Db
{
    private $_config = NULL;
    private static $_slaveInstance = NULL;
    private static $_masterInstance = NULL;
    private $_adodbInited = 0;
    private $_connected = NULL;
    private $_queriesCount = NULL;
    private $_db = NULL;
    private $_disabled = NULL;
    protected $_transactionManager = NULL;
    public $prefix = NULL;
    /*
    ERROR in processing the function: Object reference not set to an instance of an object.
       at a4c0de.PHP.Ioncube.ZNode.SameAs(ZNode zn)
       at a4c0de.PHP.Parsers.OpcodeParser.processOpcode_7_1()
       at a4c0de.PHP.Parsers.OpcodeParser.parse()
       at a4c0de.PHP.Output.CodeGenerator.outputClassMethod(BinaryTextWriter writer, ZOpArray zoparray, String indent, Boolean isInInterface)
    */
    public static function instance()
    {
        if (!isset($instance)) {
            $cnf = \Traffic\Service\ConfigService::instance()->get("db");
            self::$_masterInstance = new Db($cnf);
        }
        return self::$_masterInstance;
    }
    protected function __construct($cnf)
    {
        $this->_config = $cnf;
        if (!$this->_adodbInited) {
            $this->_initAdodb();
        }
    }
    public function connect()
    {
        if ($this->isConnected()) {
            return NULL;
        }
        $this->_transactionManager = new TransactionManager();
        $this->_initAdodb();
        \Traffic\Logging\Service\LoggerService::instance()->debug("Connect to DB");
        $this->_openConnection($this->_config);
        $this->_connected = true;
    }
    public function disconnect()
    {
        if ($this->isConnected()) {
            $this->_db->disconnect();
            $this->_connected = false;
        }
    }
    public function disable()
    {
        $this->_disabled = true;
    }
    public function enable()
    {
        $this->_disabled = false;
    }
    public function isEnabled()
    {
        return !$this->_disabled;
    }
    public static function getPrefix()
    {
        return \Traffic\Service\ConfigService::instance()->get("db", "prefix");
    }
    public static function quote($value)
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_null($value)) {
            return "NULL";
        }
        if (is_float($value)) {
            return sprintf("%F", $value);
        }
        if (is_array($value)) {
            return array_map(function ($n) {
                return Db::quote($n);
            }, $value);
        }
        return "'" . addcslashes($value, "\0\n\r\\'\32") . "'";
    }
    public function isConnected()
    {
        return $this->_connected;
    }
    public function getQueriesCount()
    {
        return $this->_queriesCount;
    }
    protected function _initAdodb()
    {
        include_once ADODB_DIR . "/adodb-exceptions.inc.php";
        $GLOBALS["ADODB_CACHE_DIR"] = ROOT . \Traffic\Cache\Cache::DEFAULT_CACHE_DIR;
        $GLOBALS["ADODB_COUNTRECS"] = false;
        $this->_adodbInited = true;
    }
    protected function _openConnection($cnf)
    {
        if (empty($cnf["user"])) {
            throw new \Core\Exception("Db configuration is empty");
        }
        if (class_exists("PDO") && in_array("mysql", \PDO::getAvailableDrivers())) {
            $this->_db = NewADOConnection("pdo");
            $server = preg_replace("/:[0-9]+/", "", $cnf["server"]);
            if (!empty($cnf["port"])) {
                $server .= ";port=" . $cnf["port"];
            }
            $this->_db->connect("mysql:host=" . $server, $cnf["user"], $cnf["password"], $cnf["name"]);
        } else {
            $this->_db = NewADOConnection("mysql");
            $this->_db->connect($cnf["server"], $cnf["user"], $cnf["password"], $cnf["name"]);
        }
        $this->_db->setFetchMode(ADODB_FETCH_ASSOC);
        $this->_db->execute("SET character_set_client=utf8");
        $this->_db->execute("SET character_set_connection=utf8");
        $this->_db->execute("SET character_set_results=utf8");
        $this->_db->execute("SET collation_connection=utf8_unicode_ci");
        $this->_db->execute("SET SQL_BIG_SELECTS=1");
        $this->_db->execute("SET sql_mode=''");
    }
    public function insert($table, $data, $queryPostfix = NULL)
    {
        $sql = "INSERT INTO " . $table . " ";
        $fields = array_keys($data);
        $values = array_values($data);
        $fieldsStr = "`" . implode("`, `", $fields) . "`";
        if (isset($value)) {
            if (is_array($value)) {
                throw new \Core\Exception("Field " . $fields[$num] . " is array");
            }
            $value = Db::quote($value);
        } else {
            $value = "NULL";
        }
        $sql .= "(" . $fieldsStr . ") VALUES (" . implode(",", $values) . ")";
        if ($queryPostfix) {
            $sql .= $queryPostfix;
        }
        $this->execute($sql);
        return $this->getInsertId();
    }
    public function multiInsert($table, $entries, $ignoreDuplication = false)
    {
        if (!count($entries)) {
            return NULL;
        }
        $inserts = [];
        $previousData = reset($entries);
        $fields = array_keys($previousData);
        sort($fields);
        foreach ($entries as $data) {
            $newFields = array_keys($data);
            sort($newFields);
            if ($fields != $newFields) {
                $this->_insert($table, $fields, $inserts, $ignoreDuplication);
                $fields = array_keys($data);
                $inserts = [];
            }
            $values = [];
            foreach ($fields as $field) {
                if (!is_null($data[$field])) {
                    if (is_array($data[$field])) {
                        throw new \Core\Exception("A field " . $field . " is array");
                    }
                    $values[] = Db::quote($data[$field]);
                } else {
                    $values[] = "NULL";
                }
            }
            $inserts[] = "(" . implode(",", $values) . ")";
        }
        $this->_insert($table, $fields, $inserts, $ignoreDuplication);
    }
    protected function _insert($table, $fields, $inserts, $ignoreDuplication = false)
    {
        $sql = "INSERT ";
        if ($ignoreDuplication) {
            $sql .= " IGNORE ";
        }
        $sql .= "INTO " . $table . " ";
        $fieldsStr = "`" . implode("`, `", $fields) . "`";
        $sql .= "(" . $fieldsStr . ") VALUES " . implode(",", $inserts);
        unset($inserts);
        $this->execute($sql);
        unset($sql);
    }
    public function update($table, $where, $data)
    {
        $sql = "UPDATE " . $table . " SET ";
        $fields = [];
        foreach ($data as $field => $value) {
            if ($field != "id") {
                if (isset($value)) {
                    $str = "`" . $field . "` = " . Db::quote($value) . "";
                } else {
                    $str = "`" . $field . "` = NULL";
                }
                $fields[] = $str;
            }
        }
        $sql .= implode(", ", $fields);
        $sql .= " WHERE " . $where;
        $this->execute($sql);
    }
    public function getAll($sql)
    {
        return $this->_wrapQuery("getAll", func_get_args());
    }
    public function getRow($sql)
    {
        return $this->_wrapQuery("getRow", func_get_args());
    }
    public function getOne($sql)
    {
        return $this->_wrapQuery("getOne", func_get_args());
    }
    public function fetchRows($sql)
    {
        return $this->_wrapQuery("getArray", func_get_args());
    }
    public function getFoundRowsCount()
    {
        return $this->getOne("SELECT FOUND_ROWS()");
    }
    public function fetchRow($sql)
    {
        return $this->_wrapQuery("getRow", func_get_args());
    }
    public function execute($sql)
    {
        return $this->_wrapQuery("execute", func_get_args());
    }
    public function getAffectedRows()
    {
        return $this->_wrapQuery("affected_rows", func_get_args());
    }
    public function executeUntilFinished($sql, $limit = 10000, $maxIterations = 10000)
    {
        $i = 0;
        while ($i < $maxIterations) {
            $this->execute($sql . " LIMIT " . $limit);
            $affectedRows = $this->getAffectedRows();
            if ($affectedRows >= $limit) {
                $i++;
            }
        }
    }
    public function beginTransaction()
    {
        $this->connect();
        $this->_transactionManager->begin();
    }
    public function commit()
    {
        $this->connect();
        $this->_transactionManager->commit();
    }
    public function rollback()
    {
        $this->connect();
        $this->_transactionManager->rollback();
    }
    public function rollbackAll()
    {
        $this->connect();
        $this->_transactionManager->rollbackAll();
    }
    public function startFastInsert()
    {
        $this->execute("SET unique_checks=0");
    }
    public function endFastInsert()
    {
        $this->execute("SET unique_checks=1");
    }
    public function getInsertId()
    {
        return $this->_db->insert_id();
    }
    public function _wrapQuery($method, $args)
    {
        if ($this->_disabled) {
            $message = "Db is disabled. " . $method . ": " . json_encode($args);
            adodb_throw("ADOdb_Active_Record", $method, -1, $message, 0, 0, false);
        }
        $this->connect();
        $this->_queriesCount++;
        $startTime = microtime(true);
        if (!$this->_db->IsConnected()) {
            adodb_throw(ADOdb_Active_Record, $method, -1, "Db is not connected", 0, 0, false);
        }
        $result = call_user_func_array([$this->_db, $method], $args);
        if (isset($startTime)) {
            $sql = implode(" ", $args);
            $time = number_format(microtime(true) - $startTime, 4) . " sec.";
            \Traffic\Logging\Service\LoggerService::instance()->debug("[SQL:" . $method . "] " . $sql . " (" . $time . ")");
        }
        return $result;
    }
    public function size()
    {
        $name = \Traffic\Service\ConfigService::instance()->get("db", "name");
        $sql = "SELECT ROUND(SUM(data_length + index_length), 1)\n            FROM information_schema.tables \n            WHERE table_schema = '" . $name . "'";
        try {
            return $this->getOne($sql);
        } catch (\ADODB_Exception $e) {
        }
    }
    public function transactionManager()
    {
        return $this->_transactionManager;
    }
}

?>