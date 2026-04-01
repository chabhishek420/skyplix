<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class Pipeline
{
    protected $_pipeline = NULL;
    public function __construct()
    {
        $this->_pipeline = [new ResolveClickDevice(), new ExtractReferences(), new ExtractVisitors(), new ExtractParentSubId(), new FilterAttributes(false), new SaveClicks()];
    }
    public function process($entries)
    {
        foreach ($this->_pipeline as $stage) {
            $stageName = \Traffic\Tools\Tools::demodulize(get_class($stage));
            \Traffic\Logging\Service\LoggerService::instance()->debug(\Traffic\Profiler\ProfilerService::instance()->step($stageName . " start)"));
            $entries = $stage->process($entries);
            \Traffic\Logging\Service\LoggerService::instance()->debug(\Traffic\Profiler\ProfilerService::instance()->step($stageName . " end"));
        }
    }
}

?>