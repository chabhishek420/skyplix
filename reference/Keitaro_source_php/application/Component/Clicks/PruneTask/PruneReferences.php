<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\PruneTask;

class PruneReferences implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::REFERENCE_TYPE;
    }
    public function prune()
    {
        \Component\Cleaner\Service\CleanerService::instance()->pruneReferences();
        \Component\Cleaner\Service\CleanerService::instance()->pruneClickLinks();
    }
}

?>