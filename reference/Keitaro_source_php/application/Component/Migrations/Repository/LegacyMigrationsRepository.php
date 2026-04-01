<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Migrations\Repository;

class LegacyMigrationsRepository extends AbstractMigrationRepository
{
    public function __construct()
    {
        require_once ROOT . "/application/Component/Migrations/Migration.php";
        $this->_path = ROOT . "/application/migrations";
        parent::__construct();
    }
    public function getMigrations()
    {
        return $this->getItems();
    }
    public function getMigration($version)
    {
        return $this->getItem($version);
    }
    protected function _load($filePath, $key)
    {
        $key = ltrim($key, "0");
        if (!isset($this->_items[$key])) {
            $className = "Migration_" . $key;
            if (!class_exists($className)) {
                require_once $filePath;
            }
            if (!class_exists($className)) {
                $msg = "Migration class \"" . $className . "\" not found";
                throw new \Component\Migrations\MigrationError($msg);
            }
            $this->_items[$key] = new $className();
        }
    }
}

?>