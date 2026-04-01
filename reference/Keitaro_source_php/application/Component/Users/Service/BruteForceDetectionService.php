<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Service;

class BruteForceDetectionService extends \Traffic\Service\AbstractService
{
    const BLOCK_TIME = 120;
    public function flush()
    {
        @unlink(@$this->_getStorage());
    }
    public function increaseFailCount($ip)
    {
        $blockStore = $this->_load();
        $max = \Traffic\Service\ConfigService::instance()->get("system", "max_auth_tries", 20);
        if (!isset($blockStore[$ip])) {
            $blockStore[$ip] = ["count" => 0, "blocked" => false];
        }
        if (isset($blockStore[$ip]) && $max - 1 <= $blockStore[$ip]["count"]) {
            $this->block($ip);
        } else {
            $blockStore[$ip]["count"]++;
            $this->_save($blockStore);
        }
    }
    public function block($ip)
    {
        $blockStore = $this->_load();
        $blockStore[$ip]["count"] = 0;
        $blockStore[$ip]["blocked"] = true;
        $blockStore[$ip]["time"] = time() + BLOCK_TIME;
        $this->_save($blockStore);
    }
    public function isBlocked($ip)
    {
        $this->prune();
        $blockStore = $this->_load();
        if (isset($blockStore[$ip]) && isset($blockStore[$ip]["blocked"])) {
            return $blockStore[$ip]["blocked"];
        }
        return false;
    }
    public function prune($now = NULL)
    {
        if (empty($now)) {
            $now = new \DateTime();
        }
        $blockStore = $this->_load();
        $updated = false;
        foreach ($blockStore as $ip => $row) {
            if (isset($row["blocked"]) && $row["blocked"] && $row["time"] <= $now->getTimestamp()) {
                $blockStore[$ip] = [];
                $updated = true;
            }
        }
        if ($updated) {
            $this->_save($blockStore);
        }
    }
    public function getBlockExpiresAt($ip)
    {
        $blockStore = $this->_load();
        return $blockStore[$ip]["time"] - time();
    }
    private function _load()
    {
        if (!is_dir(ROOT . "/var/auth/")) {
            mkdir(ROOT . "/var/auth/");
        }
        if (!file_exists($this->_getStorage())) {
            return [];
        }
        $data = file_get_contents($this->_getStorage());
        if ($data) {
            return unserialize($data);
        }
    }
    private function _save($blockStore)
    {
        $fileName = ROOT . "/var/auth/store.dat";
        $data = serialize($blockStore);
        file_put_contents($fileName, $data);
    }
    private function _getStorage()
    {
        return ROOT . "/var/auth/store.dat";
    }
}

?>