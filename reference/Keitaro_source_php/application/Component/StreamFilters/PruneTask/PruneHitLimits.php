<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\PruneTask;

class PruneHitLimits implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::GENERAL_TYPE;
    }
    public function prune()
    {
        \Traffic\HitLimit\Service\HitLimitService::instance()->prune(new \DateTime());
    }
}

?>