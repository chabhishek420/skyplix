<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\PruneTask;

class Pruner
{
    private $_service = NULL;
    private $_ttl = NULL;
    private $_allowedOptions = ["service", "ttl"];
    public function __construct($options = [])
    {
        $this->_ttl = \Traffic\Repository\CachedSettingsRepository::instance()->get("archive_ttl");
        foreach ($options as $key => $value) {
            $name = "_" . $key;
            if (!in_array($key, $this->_allowedOptions)) {
                throw new \Core\Application\Exception\Error("Incorrect option " . $key);
            }
            $this->{$name} = $value;
        }
    }
    public function getTtl()
    {
        return $this->_ttl;
    }
    public function isCleanDisabled()
    {
        if (empty($this->_ttl) || $this->_ttl == 0) {
            return true;
        }
        return false;
    }
    public function prune()
    {
        if ($this->isCleanDisabled()) {
            return NULL;
        }
        $this->pruneBefore($this->getExpirationDateTime());
    }
    public function getExpirationDateTime()
    {
        return new \DateTime("-" . $this->_ttl . " days");
    }
    public function pruneBefore(\DateTime $dateTime)
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $where .= " AND updated_at < " . \Core\Db\Db::quote($dateTime->format(\Core\Model\AbstractModel::DATETIME_FORMAT));
        $this->_service->deleteMany($where);
    }
    public function pruneAll()
    {
        $where = "state = " . \Core\Db\Db::quote(\Core\Entity\State::DELETED);
        $this->_service->deleteMany($where);
    }
}

?>