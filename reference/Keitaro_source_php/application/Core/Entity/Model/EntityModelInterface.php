<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\Model;

final class EntityModelInterface
{
    public abstract function getId();
    public abstract function get($key);
    public abstract function set($key, $value);
    public abstract function setData($data);
    public abstract function getData();
    public abstract function restoreData($data);
    public abstract function delete();
    public abstract function save();
    public abstract function reload();
}

?>