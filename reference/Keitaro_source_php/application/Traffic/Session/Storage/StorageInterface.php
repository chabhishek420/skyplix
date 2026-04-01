<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Session\Storage;

final class StorageInterface
{
    public abstract function save($save, $uniquenessId, \Traffic\Session\SessionEntry $entry, string $ttlInSec);
    public abstract function getSessionEntry(\Traffic\Session\SessionEntry $getSessionEntry, $uniquenessId);
}

?>