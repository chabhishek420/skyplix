<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Campaigns\Serializer;

class CampaignSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    private $_extended = NULL;
    private $_withStreams = NULL;
    public function __construct($extended = false, $withStreams = false)
    {
        $this->_extended = $extended;
        $this->_withStreams = $withStreams;
    }
    public function extra($obj, $data)
    {
        while (isset($data["cost_type"]) && $data["cost_type"] == "CPV") {
            $data["cost_type"] = "CPC";
        }
        $data["domain"] = NULL;
        if (!empty($data["domain_id"])) {
            try {
                $domain = \Component\Domains\Repository\DomainsRepository::instance()->findFirst("id = " . \Core\Db\Db::quote($data["domain_id"]));
                if (!empty($domain)) {
                    $data["domain"] = \Component\Domains\Service\DomainService::instance()->urlWithBasePath($domain, "/");
                }
            } catch (\Core\Exceptions\NotFoundError $e) {
            }
        } else {
            $data["domain_id"] = NULL;
        }
        if ($this->_extended) {
            if (empty($data["group_id"])) {
                $data["group"] = \Core\Locale\LocaleService::t("groups.default");
                $data["group_id"] = 0;
            } else {
                $data["group"] = \Component\Groups\Repository\GroupsRepository::instance()->getName($data["group_id"]);
            }
            $data["streams_count"] = \Component\Streams\Repository\StreamRepository::instance()->getCampaignStreamsCount($obj);
            if (!empty($data["traffic_source_id"])) {
                $data["ts"] = \Component\TrafficSources\Repository\TrafficSourceRepository::instance()->getName($data["traffic_source_id"]);
            } else {
                $data["traffic_source_id"] = NULL;
                $data["ts"] = NULL;
            }
            $postbacks = \Component\Campaigns\Repository\CampaignPostbackRepository::instance()->getCampaignPostbacks($obj);
            $data["postbacks"] = \Core\Json\SerializerFactory::serialize($postbacks, new CampaignPostbackSerializer());
        }
        if ($this->_withStreams) {
            $streams = \Component\Streams\Repository\StreamRepository::instance()->allOrderedStreamsForCampaign($obj);
            $data["streams"] = \Core\Json\SerializerFactory::serialize($streams, new \Component\Streams\Serializer\StreamSerializer(true, true));
        }
        if ($obj->isCostRevShare()) {
            $data["cost_value"] = intval($data["cost_value"]);
        }
        unset($data["mode"]);
        $data = $this->_flatTimestamps($data);
        return $data;
    }
}

?>