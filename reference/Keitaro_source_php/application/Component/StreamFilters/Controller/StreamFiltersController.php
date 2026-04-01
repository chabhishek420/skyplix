<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Controller;

class StreamFiltersController extends \Admin\Controller\BaseController
{
    public function filtersAction()
    {
        return \Component\StreamFilters\Repository\FilterRepository::instance()->getFiltersAsOptions();
    }
}

?>