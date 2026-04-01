<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Users\Repository;

class AclResourceRepository extends \Core\Entity\Repository\EntityRepository
{
    private $_mandatory = NULL;
    private $_complementary = NULL;
    private $_admin = NULL;
    private $_legacy = NULL;
    private $_translation = NULL;
    private $_defaultForNewUsers = NULL;
    private $_resourceBindings = NULL;
    public function definition()
    {
        return \Component\Users\Model\AclResource::definition();
    }
    public function getAll()
    {
        return array_merge($this->getComplementary(), $this->getMandatory());
    }
    private function _fillLegacy()
    {
        $this->_legacy = [];
    }
    private function _fillMandatory()
    {
        $this->_mandatory = ["home", "profile", "status", "search", "macros", "diagnostics", "codePresets", "dics", "userPreferences", "favourite_streams", "streamActions", "editor", "tpimandatory", "kClientJSPreset"];
    }
    private function _fillComplementary()
    {
        $this->_complementary = ["affiliate_networks", "archive", "campaigns", "cleaner", "clicks", "conversions", "dashboard", "geo_profiles", "landings", "migrations", "offers", "reports", "streams", "traffic_sources", "trends", "groups", "labels", "domains", "api_keys", "geo_dbs", "integrations"];
    }
    public function _fillAdmin()
    {
        $this->_admin = ["self_update", "settings", "botlist", "logs", "geo_dbs", "users", "branding", "ipInfoDataTypes", "integrations"];
    }
    private function _fillTranslations()
    {
        $this->_translation = ["clicks" => "clicks.log", "postback" => "postbacks.title", "conversions" => "resources.conversions", "labels" => "reports.labels.title", "api_keys" => "profile.api_keys.title", "integrations" => "third_party_integration.title"];
    }
    private function _fillDefaultForNewUsers()
    {
        $this->_defaultForNewUsers = ["offers", "landings", "campaigns", "groups", "affiliate_networks", "traffic_sources", "streams", "reports", "trends", "groups", "clicks", "labels", "api_keys"];
    }
    private function _fillResourceBindings()
    {
        $this->_resourceBindings = ["affiliateNetworkTemplates" => "affiliate_networks", "affiliateNetworks" => "affiliate_networks", "apiKeys" => "api_keys", "exportedReports" => "reports", "favouriteReports" => "reports", "geoProfiles" => "geo_profiles", "geoDbs" => "geo_dbs", "legacyMigrations" => "migrations", "StreamEvents" => "streams", "Triggers" => "streams", "collections" => "campaigns", "resources" => "users", "selfUpdates" => "self_update", "streamFilters" => "streams", "streamSchemas" => "streams", "streamTypes" => "streams", "system" => "self_update", "favouriteStreams" => "favourite_streams", "trafficSourceTemplates" => "traffic_sources", "trafficSources" => "traffic_sources", "traffic_sources" => "traffic_sources", "CodePresets" => "codePresets", "thirdpartyintegration" => "integrations", "facebookintegration" => "integrations", "appsflyerintegration" => "integrations", "adminApi" => "api_keys", "logs" => "streams"];
    }
    public function __construct()
    {
        $this->_fillMandatory();
        $this->_fillComplementary();
        $this->_fillTranslations();
        $this->_fillAdmin();
        $this->_fillLegacy();
        $this->_fillDefaultForNewUsers();
        $this->_fillResourceBindings();
    }
    public function getMandatory()
    {
        return $this->_mandatory;
    }
    public function getComplementary()
    {
        return $this->_complementary;
    }
    public function getAdmin()
    {
        return $this->_admin;
    }
    public function getLegacy()
    {
        return $this->_legacy;
    }
    public function getDefaultForNewUsers()
    {
        return $this->_defaultForNewUsers;
    }
    public function getComplementaryAsOptions()
    {
        $result = [];
        foreach ($this->getComplementary() as $resource) {
            if (isset($this->_translation[$resource])) {
                $translateKey = $this->_translation[$resource];
            } else {
                $translateKey = $resource . ".title";
            }
            $result[] = ["name" => \Core\Locale\LocaleService::t($translateKey), "value" => $resource];
        }
        return $result;
    }
    public function getRootResource($resource)
    {
        if (is_string($resource) && isset($this->_resourceBindings[$resource])) {
            return $this->_resourceBindings[$resource];
        }
        return $resource;
    }
    public function expandList($resources)
    {
        $resourcesDic = [];
        foreach ($this->_resourceBindings as $child => $root) {
            $resourcesDic[$root][] = $child;
        }
        $result = [];
        foreach ($resources as $resource) {
            if (is_string($resource) && isset($resourcesDic[$resource])) {
                $result = array_merge($result, $resourcesDic[$resource]);
            }
            $result[] = $resource;
        }
        return $result;
    }
}

?>