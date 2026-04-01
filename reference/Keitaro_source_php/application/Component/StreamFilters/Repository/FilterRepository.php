<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\StreamFilters\Repository;

class FilterRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_exclude = ["referer", "example", "uniqueness_cookie", "uniqueness_ip"];
    private $_filters = [];
    private $_aliases = [];
    const ABSTRACT_FILTER_FILE = "AbstractFilter";
    const CUSTOM_FILTER_NAMESPACE = "\\Filters\\";
    const DEFAULT_CUSTOM_PATH = "/application/filters";
    public function loadFilters()
    {
        if (!empty($this->_filters)) {
            return NULL;
        }
        $this->register("interval", new \Component\StreamFilters\Filter\Interval());
        $this->register("limit", new \Component\StreamFilters\Filter\Limit());
        $this->register("parameter", new \Component\StreamFilters\Filter\Parameter());
        $this->register("proxy", new \Component\StreamFilters\Filter\Proxy());
        $this->register("referrer", new \Component\StreamFilters\Filter\Referrer());
        $this->register("schedule", new \Component\StreamFilters\Filter\Schedule());
        $this->register("uniqueness", new \Component\StreamFilters\Filter\Uniqueness());
        $this->alias("uniqueness_cookie", "uniqueness");
        $this->alias("uniqueness_ip", "uniqueness");
        $this->register("source", new \Component\StreamFilters\Filter\AnyParam("source", "parameters"));
        $this->register("x_requested_with", new \Component\StreamFilters\Filter\AnyParam("x_requested_with", "parameters"));
        $this->register("keyword", new \Component\StreamFilters\Filter\AnyParam("keyword", "parameters"));
        $this->register("search_engine", new \Component\StreamFilters\Filter\AnyParam("search_engine", "parameters"));
        $this->register("ad_campaign_id", new \Component\StreamFilters\Filter\AnyParam("ad_campaign_id", "parameters"));
        $this->register("creative_id", new \Component\StreamFilters\Filter\AnyParam("creative_id", "parameters"));
        for ($i = 1; $i <= \Traffic\Repository\ParameterRepository::instance()->getSubIdCount(); $i++) {
            $param = "sub_id_" . $i;
            $this->register($param, new \Component\StreamFilters\Filter\AnyParam($param, "sub_ids"));
        }
        for ($i = 1; $i <= \Traffic\Repository\ParameterRepository::instance()->getExtraParamCount(); $i++) {
            $param = "extra_param_" . $i;
            $this->register($param, new \Component\StreamFilters\Filter\AnyParam($param, "extra_params"));
        }
        $this->register("bot", new \Component\StreamFilters\Filter\IsBot());
        $this->register("city", new \Component\StreamFilters\Filter\City());
        $this->register("region", new \Component\StreamFilters\Filter\Region());
        $this->register("country", new \Component\StreamFilters\Filter\Country());
        $this->register("connection_type", new \Component\StreamFilters\Filter\ConnectionType());
        $this->register("empty_referrer", new \Component\StreamFilters\Filter\EmptyReferrer());
        $this->register("ip", new \Component\StreamFilters\Filter\Ip());
        $this->register("ipv_6", new \Component\StreamFilters\Filter\Ipv6());
        $this->register("operator", new \Component\StreamFilters\Filter\Operator());
        $this->register("isp", new \Component\StreamFilters\Filter\Isp());
        $this->register("browser", new \Component\StreamFilters\Filter\Browser());
        $this->register("browser_version", new \Component\StreamFilters\Filter\BrowserVersion());
        $this->register("device_model", new \Component\StreamFilters\Filter\DeviceModel());
        $this->register("device_type", new \Component\StreamFilters\Filter\DeviceType());
        $this->register("os", new \Component\StreamFilters\Filter\Os());
        $this->register("os_version", new \Component\StreamFilters\Filter\OsVersion());
        $this->register("user_agent", new \Component\StreamFilters\Filter\UserAgent());
        $this->register("language", new \Component\StreamFilters\Filter\Language());
        $this->register("imklo_detect", new \Component\StreamFilters\Filter\ImkloDetect());
        $this->register("hide_click_detect", new \Component\StreamFilters\Filter\HideClickDetect());
        if (empty($customPath)) {
            $customPath = ROOT . DEFAULT_CUSTOM_PATH;
        }
        $this->loadCustomFilters($customPath);
    }
    public function register($key, \Core\Filter\AbstractFilter $obj)
    {
        if (!empty($this->_filters[$key])) {
            throw new \Core\Registry\RegistryEntryExists("Filter '" . $key . "' is already exists");
        }
        $this->_filters[$key] = $obj;
    }
    public function alias($aliasName, $name)
    {
        if (empty($this->_filters[$name])) {
            throw new \Core\Application\Exception\Error("Filter " . $name . " is not defined");
        }
        if (!empty($this->_filters[$aliasName])) {
            throw new \Core\Application\Exception\Error("Filter " . $aliasName . " is already defined");
        }
        $this->_aliases[] = $aliasName;
        $this->_filters[$aliasName] = $this->_filters[$name];
    }
    public function getFilters()
    {
        $this->loadFilters();
        return $this->_filters;
    }
    public function getFiltersAsOptions(\Traffic\Model\TrafficSource $ts = NULL)
    {
        $this->loadFilters();
        $options = [];
        foreach ($this->_filters as $key => $filter) {
            if (!in_array($key, $this->_exclude) && !in_array($key, $this->_aliases)) {
                $options[] = $filter->getInfo($ts);
            }
        }
        return array_values($options);
    }
    public function getFilter($key)
    {
        $this->loadFilters();
        if (empty($this->_filters[$key])) {
            throw new \Core\Registry\RegistryEntryNotFound("Filter " . $key . " not found");
        }
        return $this->_filters[$key];
    }
    public function getActiveFilters()
    {
        $filters = $this->getFilters();
        foreach ($this->_exclude as $key) {
            unset($filters[$key]);
        }
        return $filters;
    }
    public function getFilterNames()
    {
        $filters = $this->getActiveFilters();
        $names = array_keys($filters);
        $names = array_merge($names, $this->_aliases);
        sort($names);
        return $names;
    }
    public function loadCustomFilters($customPath)
    {
        $iterator = new \DirectoryIterator($customPath);
        foreach ($iterator as $file) {
            if ($this->_shouldLoad($file)) {
                $name = str_replace(".php", "", $file->getFilename());
                if (!isset($this->_filters[$name])) {
                    try {
                        $className = $this->_checkClassExist($file, $name);
                        $filter = new $className();
                        $this->register($filter->getKey(), $filter);
                    } catch (\Error $e) {
                        $error = $e->getMessage();
                        \Traffic\Logging\Service\LoggerService::instance()->error("Failed loading custom filter " . $name . ": " . $error);
                    }
                }
            }
        }
    }
    private function _shouldLoad(\SplFileInfo $file)
    {
        return $file->isFile() && !strstr($file->getFilename(), ABSTRACT_FILTER_FILE) && substr($file->getFilename(), -3, 3) === "php";
    }
    private function _checkClassExist($file, $name)
    {
        $className = CUSTOM_FILTER_NAMESPACE . $name;
        if (class_exists($className, false)) {
            return $className;
        }
        $nameInCode = $this->_findClassNameInCode($file->getPathname());
        $className = CUSTOM_FILTER_NAMESPACE . $nameInCode;
        if (class_exists($className, false)) {
            return $className;
        }
        \Core\Kernel\CustomFilterLoader::load($file->getPathname());
        if (class_exists($className, false)) {
            return $className;
        }
        $msg = "Failed loading custom filter " . $name . ". Error loading class.";
        throw new \Core\Application\Exception\Error($msg);
    }
    private function _findClassNameInCode($path)
    {
        $code = file_get_contents($path);
        preg_match("/^class (.+?) extends/sm", $code, $match);
        if (!empty($match)) {
            return $match[1];
        }
        return false;
    }
}

?>