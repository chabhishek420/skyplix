<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class ExtractVisitors implements StageInterface
{
    public function process($entries)
    {
        $container = new ExtractVisitors\VisitorAggregator();
        $entries = $this->_prepare($entries, $container);
        $entries = $this->_assign($entries, $container);
        return $entries;
    }
    private function _prepare($entries, ExtractVisitors\VisitorAggregator $aggregator)
    {
        foreach ($entries as $click) {
            $aggregator->add($click);
        }
        $aggregator->loadIds();
        $aggregator->commit();
        return $entries;
    }
    private function _assign($entries, ExtractVisitors\VisitorAggregator $aggregator)
    {
        for ($i = 0; $i < count($entries); $i++) {
            $entries[$i]["visitor_id"] = $aggregator->getId($entries[$i]["visitor_code"])["id"];
        }
        return $entries;
    }
}

?>