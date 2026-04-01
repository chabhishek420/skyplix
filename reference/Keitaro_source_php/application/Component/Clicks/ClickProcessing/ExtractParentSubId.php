<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ClickProcessing;

class ExtractParentSubId
{
    const VALUE_LIMIT = 255;
    public function process($entries)
    {
        $aggregator = new ExtractParentSubId\ParentSubIdAggregator();
        $this->_prepare($entries, $aggregator);
        return $entries;
    }
    private function _prepare($rawClicks, ExtractParentSubId\ParentSubIdAggregator $container)
    {
        foreach ($rawClicks as $rawClick) {
            if (!empty($rawClick["parent_sub_id"])) {
                $container->add($rawClick);
            }
        }
        $container->commit();
    }
}

?>