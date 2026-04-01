<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Streams;

class ExportStreams
{
    private $_campaign = NULL;
    private $_format = NULL;
    private $_streamAttributesExclusions = ["id", "updated_at"];
    private $_filterAttributesExclusions = ["id", "stream_id"];
    private $_filterAssociationExclusions = ["id", "stream_id", "updated_at"];
    const TIMEOUT = 300;
    const PATH = "/exports/";
    public function __construct(\Traffic\Model\Campaign $campaign = NULL)
    {
        if (\Core\Application\Application::instance()->isTesting()) {
            set_time_limit(TIMEOUT);
        }
        $this->setCampaign($campaign);
    }
    public function setCampaign(\Traffic\Model\Campaign $campaign)
    {
        $this->_campaign = $campaign;
        return $this;
    }
    public function getCampaign()
    {
        return $this->_campaign;
    }
    public function setFormat($type)
    {
        $this->_format = $type;
        return $this;
    }
    public function getFormat()
    {
        return $this->_format;
    }
    public function export()
    {
        $serializer = new Serializer\DumpedStreamSerializer();
        $items = [];
        foreach (Repository\StreamRepository::instance()->getCampaignStreams($this->getCampaign()) as $stream) {
            $item = $serializer->serialize($stream, $this->_streamAttributesExclusions);
            $item["filters"] = $this->_getFilters($stream);
            $item["landings"] = $this->_getLandings($stream);
            $item["offers"] = $this->_getOffers($stream);
            $items[] = $item;
        }
        $fileContent = json_encode($items);
        $fileName = "streams_" . $this->getCampaign()->getId() . "_" . date("Y-m-d") . ".json";
        $path = ROOT . PATH;
        if (!is_dir($path)) {
            mkdir($path, 511);
        }
        if (!file_put_contents($path . $fileName, $fileContent)) {
            throw new Exception\ImportExportError(\Core\Locale\LocaleService::t("streams.export.no_permission"));
        }
        return PATH . $fileName;
    }
    private function _getFilters(\Traffic\Model\BaseStream $stream)
    {
        $serializer = new \Component\StreamFilters\Serializer\StreamFilterSerializer();
        $filters = [];
        foreach (\Component\StreamFilters\Repository\StreamFilterRepository::instance()->allByStream($stream) as $filter) {
            $filters[] = $serializer->serialize($filter, $this->_filterAttributesExclusions);
        }
        return $filters;
    }
    private function _getLandings(\Traffic\Model\BaseStream $stream)
    {
        $serializer = new \Component\Landings\Serializer\StreamLandingAssociationSerializer();
        $landingAssociations = [];
        foreach (Repository\StreamLandingAssociationRepository::instance()->allByStream($stream) as $association) {
            $landingAssociations[] = $serializer->serialize($association, $this->_filterAssociationExclusions);
        }
        return $landingAssociations;
    }
    private function _getOffers(\Traffic\Model\BaseStream $stream)
    {
        $serializer = new \Component\Offers\Serializer\StreamOfferAssociationSerializer();
        $offerAssociations = [];
        foreach (Repository\StreamOfferAssociationRepository::instance()->allByStream($stream) as $association) {
            $offerAssociations[] = $serializer->serialize($association, $this->_filterAssociationExclusions);
        }
        return $offerAssociations;
    }
}

?>