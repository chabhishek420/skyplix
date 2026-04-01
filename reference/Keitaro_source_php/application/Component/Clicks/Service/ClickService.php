<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\Service;

class ClickService extends \Core\Entity\Service\EntityService
{
    const SUBID_SEQ = "SUBIDSEQ";
    public function definition()
    {
        return \Traffic\Model\Click::definition();
    }
}

?>