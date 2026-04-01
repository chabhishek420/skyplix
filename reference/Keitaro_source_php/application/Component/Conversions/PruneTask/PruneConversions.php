<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\PruneTask;

class PruneConversions implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::REFERENCE_TYPE;
    }
    public function prune()
    {
        \Component\Cleaner\Service\CleanerService::instance()->pruneConversions();
    }
    public function deleteAll()
    {
    }
}

?>