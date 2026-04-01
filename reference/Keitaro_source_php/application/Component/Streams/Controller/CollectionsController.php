<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Controller;

class CollectionsController extends \Admin\Controller\BaseController
{
    public function browsersAction()
    {
        return \Component\Device\Repository\BrowsersRepository::instance()->getBrowsers();
    }
    public function countriesAction()
    {
        $only = $this->getParam("only");
        if (!empty($only)) {
            return \Component\GeoDb\Repository\CountriesRepository::instance()->only(explode(",", $only));
        }
        return \Component\GeoDb\Repository\CountriesRepository::instance()->getCountries($this->getParam("addBlank"), $this->getParam("exclude_unknown"));
    }
    public function citiesAction()
    {
        $query = $this->getParam("query");
        $limit = $this->getParam("limit");
        return \Component\GeoDb\Repository\CitiesRepository::instance()->search($query, $limit);
    }
    public function ispAction()
    {
        return \Component\GeoDb\Repository\IspRepository::instance()->search($this->getParam("q"), $this->getParam("limit"));
    }
    public function regionsAction()
    {
        $query = $this->getParam("query");
        $only = $this->getParam("only");
        $limit = $this->getParam("limit");
        if (!empty($only)) {
            if (!is_array($only)) {
                $only = explode(",", $only);
            }
            return \Component\GeoDb\Repository\RegionsRepository::instance()->only($only, $limit);
        }
        return \Component\GeoDb\Repository\RegionsRepository::instance()->search($query, false, $limit);
    }
    public function languagesAction()
    {
        $only = $this->getParam("only");
        if (!empty($only)) {
            return \Component\Device\Repository\LanguagesRepository::instance()->only(explode(",", $only));
        }
        return \Component\Device\Repository\LanguagesRepository::instance()->getLanguages();
    }
    public function osAction()
    {
        return \Component\Device\Repository\OsRepository::instance()->getOs();
    }
    public function deviceModelsAction()
    {
        $query = $this->getParam("query");
        $limit = $this->getParam("limit");
        return \Component\Device\Repository\DeviceModelsRepository::instance()->search($query, $limit);
    }
    public function deviceTypesAction()
    {
        $only = $this->getParam("only");
        if (!empty($only)) {
            return \Component\Device\Repository\DeviceTypeRepository::instance()->only(explode(",", $only));
        }
        return \Component\Device\Repository\DeviceTypeRepository::instance()->getDeviceTypesAsOptions($this->getParam("addBlank"));
    }
    public function operatorsAction()
    {
        $addBlank = $this->getParam("addBlank");
        $query = $this->getParam("query");
        $only = $this->getParam("only");
        if ($query) {
            return \Component\GeoDb\Repository\OperatorsRepository::instance()->search($query);
        }
        return \Component\GeoDb\Repository\OperatorsRepository::instance()->getOperators($addBlank, $only);
    }
    public function connectionTypesAction()
    {
        return \Component\GeoDb\Repository\ConnectionTypesRepository::instance()->allAsOptions($this->getParam("addBlank"), $this->getParam("only"));
    }
}

?>