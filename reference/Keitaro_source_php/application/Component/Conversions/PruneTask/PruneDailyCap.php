<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\PruneTask;

class PruneDailyCap implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::GENERAL_TYPE;
    }
    public function prune()
    {
        foreach (\Component\Conversions\ConversionCapacity\Repository\ConversionCapacityRepository::instance()->getStorages() as $storage) {
            $storage->prune(new \DateTime());
        }
    }
}

?>