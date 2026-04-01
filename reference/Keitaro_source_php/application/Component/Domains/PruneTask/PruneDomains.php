<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Domains\PruneTask;

class PruneDomains extends \Component\PruneTask\BaseArchivePruneTask
{
    public function __construct()
    {
        $this->setPruner(new \Component\PruneTask\Pruner(["service" => \Component\Domains\Service\DomainService::instance()]));
    }
}

?>