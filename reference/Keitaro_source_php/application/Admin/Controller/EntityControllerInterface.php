<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Admin\Controller;

final class EntityControllerInterface
{
    public abstract function createAction();
    public abstract function updateAction();
    public abstract function archiveAction();
    public abstract function cleanArchiveAction();
}

?>