<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Av;

final class AvInterface
{
    public abstract function getError();
    public abstract function getWarnings();
    public abstract function isDetected(\Component\Triggers\Model\TriggerAssociation $trigger);
}

?>