<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model\Mixin;

interface StreamActionableMethodsTrait
{
    public function getId();
    public function getActionPayload();
    public function getName();
    public function getType();
    public function isLocal();
    public function isPreloaded();
    public function isOtherAction();
    public function getOtherAction();
    public function setActionType($value);
    public function getActionType();
    public function setActionOptions($value);
    public function setActionOption($key, $value);
    public function getActionOptions();
    public function getFolder();
    public function setFolder($folder);
}

?>