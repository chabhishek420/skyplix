<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Groups\Serializer;

class GroupSerializer extends \Core\Json\AbstractSerializer
{
    protected $_extended = false;
    protected $_fields = true;
    public function __construct($extended = false)
    {
        $this->_extended = $extended;
    }
    public function extra($obj, $data)
    {
        if ($this->_extended) {
            $data["count"] = \Component\Groups\Repository\GroupsRepository::instance()->getItemsCount($obj);
        }
        return $data;
    }
}

?>