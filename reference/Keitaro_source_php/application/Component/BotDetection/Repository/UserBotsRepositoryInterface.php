<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Repository;

final class UserBotsRepositoryInterface
{
    public abstract function getListCount();
    public abstract function getList();
    public abstract function exists($ip);
}

?>