<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection;

final class UserBotsServiceInterface
{
    public abstract function saveList($content);
    public abstract function addToList($content);
    public abstract function excludeFromList($content);
    public abstract function cleanList();
}

?>