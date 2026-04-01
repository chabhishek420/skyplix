<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\AdminApi\Controller;

class AdminApiController extends \Admin\Controller\BaseController
{
    public function indexAction()
    {
        return $this->renderView(__DIR__ . "/../views/index.phtml");
    }
    public function specAction()
    {
        return $this->redirect("https://admin-api.docs.keitaro.io/openapi.yaml");
    }
}

?>