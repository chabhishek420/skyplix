<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Service;

class SqlDumpService extends \Traffic\Service\AbstractService
{
    private $_path = NULL;
    const SCHEMA_PATH = "/../../data/schema.sql";
    public function __construct()
    {
        $this->_path = __DIR__ . SCHEMA_PATH;
    }
    public function setPath($path)
    {
        $this->_path = $path;
    }
    private function _getExcludeRegexpArray()
    {
        return ["/_index_/", "/_archive_/", "/adodb_logsql/", "/keitaro_click_destinations/", "/_stats_\\d+/"];
    }
    private function _updateAutoIncrement($content)
    {
        return preg_replace("/AUTO_INCREMENT=\\d+\\s+/im", "", $content);
    }
    private function _addIgnoreIfExists($content)
    {
        return str_replace("CREATE TABLE", "CREATE TABLE IF NOT EXISTS", $content);
    }
    private function _stripComments($content)
    {
        return preg_replace("!/\\*.*?\\*/[;\\r\\n]*!s", "", $content);
    }
    private function _addInsertSchema($content)
    {
        $sql = "SELECT `name` FROM schema_migrations ORDER BY `name` DESC";
        $rows = \Core\Db\Db::instance()->getAll($sql);
        foreach ($rows as $row) {
            $migrations[] = $row["name"];
        }
        sort($migrations);
        foreach ($migrations as $name) {
            $content .= "INSERT IGNORE INTO schema_migrations(`name`) VALUES('" . $name . "');\n";
        }
        return $content;
    }
    public function dump()
    {
        $cnf = \Traffic\Service\ConfigService::instance()->get("db");
        $server = preg_replace("/:[0-9]+/", "", $cnf["server"]);
        $settings = $dumpSettingsDefault = ["exclude-tables" => $this->_getExcludeRegexpArray(), "no-data" => true, "skip-comments" => true];
        $dsn = "mysql:host=" . $server . ";dbname=" . $cnf["name"];
        if (isset($cnf["port"])) {
            $dsn .= ";port=" . $cnf["port"];
        }
        $user = $cnf["user"];
        $password = $cnf["password"];
        $dump = new \Ifsnop\Mysqldump\Mysqldump($dsn, $user, $password, $settings);
        $dump->start($this->_path);
        $content = file_get_contents($this->_path);
        $content = $this->_updateAutoIncrement($content);
        $content = $this->_stripComments($content);
        $content = $this->_addIgnoreIfExists($content);
        $content = $this->_addInsertSchema($content);
        file_put_contents($this->_path, $content);
    }
}

?>