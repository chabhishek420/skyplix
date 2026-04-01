<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams\Serializer;

class StreamSearchResultSerializer extends StreamSerializer
{
    protected $_fields = true;
    public function extra($obj, $data)
    {
        $data = self::extra($obj, $data);
        $data["campaign_name"] = \Component\Campaigns\Repository\CampaignRepository::instance()->getNameById($data["campaign_id"]);
        return $data;
    }
}

?>