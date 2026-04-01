<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class SaveClicks implements StageInterface
{
    public function process($entries)
    {
        try {
            \Core\Db\Db::instance()->beginTransaction();
            \Core\Db\Db::instance()->multiInsert(\Traffic\Model\Click::getTableName(), $entries, true);
            \Core\Db\Db::instance()->commit();
        } catch (\ADODB_Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error("SaveClicks: " . $e->getMessage());
        }
    }
}

?>