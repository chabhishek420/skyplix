<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Repository;

class MigrationsRepository extends AbstractMigrationRepository
{
    private $_excludeMigrations = ["20190618084422_add_offer_overcap_settings"];
    const MIGRATION_FILE_NAME_PATTERN = "/^(\\d+)_([\\w_]+)\$/i";
    public function __construct()
    {
        require_once ROOT . "/application/Component/Migrations/Migration.php";
        $this->_path = ROOT . "/application/migrations2";
        parent::__construct();
    }
    public function getMigrations()
    {
        return $this->getItems();
    }
    public function getMigration($name)
    {
        $migration = $this->getItem($name);
        if (empty($migration)) {
            throw new \Component\Migrations\MigrationError("Migration '" . $name . "' not found'");
        }
        return $migration;
    }
    public static function mapFileNameToClassName($fileName)
    {
        $matches = [];
        $date = "";
        if (preg_match(MIGRATION_FILE_NAME_PATTERN, $fileName, $matches)) {
            list($date, $fileName) = $matches;
        }
        return $date . "_" . str_replace(" ", "", ucwords(str_replace("_", " ", $fileName)));
    }
    protected function _load($filePath, $key)
    {
        $key = ltrim($key, "0");
        if (!isset($this->_items[$key]) && !in_array($key, $this->_excludeMigrations)) {
            $content = file_get_contents($filePath);
            if (strpos($content, "MysqlMigration") !== false) {
                return NULL;
            }
            $className = "Migration_" . $this->mapFileNameToClassName($key);
            if (!class_exists($className)) {
                require_once $filePath;
            }
            if (!class_exists($className)) {
                $msg = "Migration class \"" . $className . "\" not found";
                throw new \Core\Application\Exception\Error($msg);
            }
            $this->_items[$className] = new $className();
        }
    }
}

?>