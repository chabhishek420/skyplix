<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\PruneTask;

class PruneUserBotDBCA implements \Component\PruneTask\PruneTaskInterface
{
    public function getType()
    {
        return \Component\PruneTask\Repository\PruneTaskRepository::GENERAL_TYPE;
    }
    public function prune()
    {
        $files = \Component\BotDetection\Repository\UserBotDBCARepository::instance()->getOldDbs(3);
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

?>