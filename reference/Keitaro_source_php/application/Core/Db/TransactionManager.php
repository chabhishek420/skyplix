<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Db;

class TransactionManager
{
    protected $_transactionLevel = 0;
    private function _getTransactionName()
    {
        return "level" . $this->_transactionLevel;
    }
    public function getTransactionLevel()
    {
        return $this->_transactionLevel;
    }
    public function begin()
    {
        if ($this->_transactionLevel === 0) {
            Db::instance()->execute("START TRANSACTION");
        } else {
            Db::instance()->execute("SAVEPOINT " . $this->_getTransactionName());
        }
        $this->_transactionLevel++;
    }
    public function commit()
    {
        $this->_transactionLevel--;
        if ($this->_transactionLevel === 0) {
            Db::instance()->execute("COMMIT");
        } else {
            Db::instance()->execute("RELEASE SAVEPOINT " . $this->_getTransactionName());
        }
    }
    public function rollback()
    {
        if ($this->_transactionLevel < 1) {
            throw new \Core\Exception("Attempt to rollback commited transaction");
        }
        $this->_transactionLevel--;
        if ($this->_transactionLevel === 0) {
            Db::instance()->execute("ROLLBACK");
        } else {
            Db::instance()->execute("ROLLBACK TO SAVEPOINT " . $this->_getTransactionName());
        }
    }
    public function rollbackAll()
    {
        for ($i = $this->_transactionLevel; 0 < $i; $i--) {
            $this->rollback();
        }
    }
}

?>