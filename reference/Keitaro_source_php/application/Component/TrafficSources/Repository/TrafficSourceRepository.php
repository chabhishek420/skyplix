<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\TrafficSources\Repository;

class TrafficSourceRepository extends \Core\Entity\Repository\EntityRepository
{
    protected $_postbackStatuses = NULL;
    const POSTBACK_STATUS_SALE = "sale";
    const POSTBACK_STATUS_LEAD = "lead";
    const POSTBACK_STATUS_REJECTED = "rejected";
    const POSTBACK_STATUS_REBILL = "rebill";
    public function definition()
    {
        return \Traffic\Model\TrafficSource::definition();
    }
    public function listAsOptions($items)
    {
        return \Core\Entity\ListOptions\Builder::build($this->definition(), $items, []);
    }
    public function getPostbackStatuses()
    {
        $result = [];
        foreach ($this->_postbackStatuses as $type) {
            $result[] = ["value" => $type, "name" => \Core\Locale\LocaleService::t("conversions.statuses." . $type)];
        }
        return $result;
    }
}

?>