<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Model;

class ClickLink extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    protected static $_fields = NULL;
    protected static $_tableName = "click_links";
    public function getSubId()
    {
        return $this->get("sub_id");
    }
    public function getParentSubId()
    {
        return $this->get("parent_sub_id");
    }
}

?>