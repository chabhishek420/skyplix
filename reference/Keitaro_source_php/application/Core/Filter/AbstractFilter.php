<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Filter;

abstract class AbstractFilter
{
    protected $_emptyQueries = NULL;
    protected $_key = NULL;
    private $_logger = NULL;
    private $_serverRequest = NULL;
    public function __construct()
    {
        $this->_emptyQueries = \Component\StreamFilters\Service\StreamFilterService::instance()->getEmptyQueries();
    }
    public function setServerRequest(\Traffic\Request\ServerRequest $serverRequest)
    {
        $this->_serverRequest = $serverRequest;
    }
    public function setLogger(\Traffic\Logging\TrafficLogEntry $logger)
    {
        $this->_logger = $logger;
    }
    public function getLogger()
    {
        return $this->_logger;
    }
    public abstract function isPass(\Traffic\Model\StreamFilter $filter, \Traffic\RawClick $rawClick);
    public function getKey()
    {
        return \Traffic\Tools\Tools::fromCamelCase(\Traffic\Tools\Tools::demodulize(get_called_class()));
    }
    public function getHeaderTemplate()
    {
        return NULL;
    }
    public function getTemplate()
    {
        $name = str_replace("_", "-", $this->getKey());
        return "<stream-" . $name . "-filter ng-model=\"filter.payload\"></stream-" . $name . "-filter>";
    }
    public function getModes()
    {
        return [\Traffic\Model\StreamFilter::ACCEPT => \Core\Locale\LocaleService::t("filters.binary_options." . \Traffic\Model\StreamFilter::ACCEPT), \Traffic\Model\StreamFilter::REJECT => \Core\Locale\LocaleService::t("filters.binary_options." . \Traffic\Model\StreamFilter::REJECT)];
    }
    public function getGroup()
    {
        return "filters.groups.other";
    }
    public function getDefaults()
    {
        return NULL;
    }
    public function getTooltip()
    {
        return NULL;
    }
    public function getInfo()
    {
        return ["value" => $this->getKey(), "tooltip" => $this->getTooltip(), "modes" => $this->getModes(), "group" => $this->getGroup(), "template" => $this->getTemplate(), "header_template" => $this->getHeaderTemplate(), "defaults" => $this->getDefaults()];
    }
    public function getServerRequest()
    {
        if (empty($this->_serverRequest)) {
            throw new \Exception("serverRequest must be defined");
        }
        return $this->_serverRequest;
    }
    protected function _findInWithRegexSupport($string, $search, $strict = true)
    {
        return \Component\StreamFilters\Service\StreamFilterService::instance()->findInWithRegexSupport($string, $search, $strict);
    }
    protected function _equalOrEmpty($string, $search)
    {
        return \Component\StreamFilters\Service\StreamFilterService::instance()->equalOrEmpty($string, $search);
    }
}

?>