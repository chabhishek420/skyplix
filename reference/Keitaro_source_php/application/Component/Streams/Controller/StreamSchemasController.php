<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Controller;

class StreamSchemasController extends \Admin\Controller\BaseController
{
    public function listAsOptionsAction()
    {
        return \Component\Streams\Repository\StreamSchemaRepository::instance()->getListAsOptions();
    }
}

?>