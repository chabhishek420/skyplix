<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams;

class ImportStreams
{
    const TIMEOUT = 300;
    const PATH = "/exports/";
    public function __construct()
    {
        if (\Core\Application\Application::instance()->isTesting()) {
            set_time_limit(TIMEOUT);
        }
    }
    public function import(\Traffic\Model\Campaign $campaign, $fileContent, $save)
    {
        $items = json_decode($fileContent, true);
        if (empty($items)) {
            throw new Exception\ImportExportError(\Core\Locale\LocaleService::t("streams.import.wrong_format"));
        }
        $result = [];
        $serializer = new Serializer\StreamSerializer();
        $definition = \Traffic\Model\Stream::definition();
        foreach ($items as $num => $data) {
            unset($data["id"]);
            $data = $this->_patchKeitaro6($data);
            $data = $this->_patchKeitaro7($data);
            $data = $this->_prepare($campaign, $data, $num);
            if ($save) {
                $data["campaign_id"] = $campaign->getId();
                foreach ($data as $field => $value) {
                    if (!$definition->hasField($field)) {
                        unset($data[$field]);
                    }
                }
                $result[] = Service\StreamService::instance()->create($data);
            } else {
                $result[] = $data;
            }
        }
        if ($save) {
            $serializer->prepare($result);
            $result = \Core\Json\SerializerFactory::serialize($result, $serializer);
            \Component\Campaigns\Service\CampaignService::instance()->resortStreams($campaign);
        }
        return $result;
    }
    private function _prepare(\Traffic\Model\Campaign $campaign, $data, $num)
    {
        if ($campaign->isTypePosition()) {
            $data["position"] = 1000 + $num;
        }
        $data["campaign_id"] = $campaign->getId();
        if (empty($data["name"])) {
            $data["name"] = $data["action_payload"];
        }
        $data["name"] = mb_substr($data["name"], 0, 90, "utf-8");
        if (empty($data["schema"])) {
            switch ($data["action_type"]) {
                case "sub_id":
                case "frame":
                case "iframe":
                case "campaign":
                case "status404":
                case "build_html":
                case "echo":
                    $schema = "action";
                    break;
                case "stream":
                case "stream_id":
                    $schema = "action";
                    $data["action_type"] = "echo";
                    $data["state"] = \Core\Entity\State::DISABLED;
                    break;
                default:
                    $schema = "redirect";
                    $data["schema"] = $schema;
            }
        }
        return $data;
    }
    private function isKeitaro6Dump($data)
    {
        foreach (\Component\Migrations\Migrator7\TdsMigrator7::getKeitaro6Fields() as $field) {
            if (!empty($data[$field])) {
                return true;
            }
        }
        return false;
    }
    private function _patchKeitaro6($data)
    {
        if (is_object($data)) {
            throw new \Core\Application\Exception\Error("stream must ne array");
        }
        $tdsMigrator7 = new \Component\Migrations\Migrator7\TdsMigrator7();
        if ($this->isKeitaro6Dump($data)) {
            $data = $tdsMigrator7->run($data);
        }
        return $data;
    }
    private function _patchKeitaro7($data)
    {
        if (isset($data["url"])) {
            $data["action_payload"] = $data["url"];
        }
        if (isset($data["redirect_type"])) {
            $data["action_type"] = $data["redirect_type"];
        }
        if (isset($data["uniq_filter"])) {
            if ($data["uniq_filter"]) {
                $data["unique_filter"] = \Traffic\Model\StreamFilter::ACCEPT;
                $data["unique_filter_scope"] = $data["uniq_filter"];
            }
            unset($data["uniq_filter"]);
        }
        if (isset($data["status"]) && !isset($data["state"])) {
            $data["state"] = $data["status"] == 1 ? \Core\Entity\State::ACTIVE : \Core\Entity\State::DISABLED;
        }
        if (empty($data["action_type"])) {
            $data["action_type"] = "http";
        }
        return $data;
    }
}

?>