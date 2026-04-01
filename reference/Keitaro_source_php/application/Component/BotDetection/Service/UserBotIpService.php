<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\BotDetection\Service;

class UserBotIpService extends \Core\Entity\Service\EntityService
{
    public function definition()
    {
        return \Component\BotDetection\Model\UserBotIp::definition();
    }
}

?>