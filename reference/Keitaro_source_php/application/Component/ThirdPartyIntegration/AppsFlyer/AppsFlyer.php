<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\ThirdPartyIntegration\AppsFlyer;

class AppsFlyer
{
    private $_api = NULL;
    const INTEGRATION = "appsflyer";
    const CONVERSION_CURRENCY = "usd";
    const CONVERSION_STATUS = "sale";
    const CONVERSION_FROM = "AppsFlyer.com";
    const APPSFLYER_SUBID = "Sub Param 1";
    const APPSFLYER_REVENUE = "Event Revenue USD";
    const APPSFLYER_ID = "AppsFlyer ID";
    public function syncConversions()
    {
        $integrations = \Component\ThirdPartyIntegration\Repository\ThirdPartyIntegrationRepository::instance()->findByIntegrationName(INTEGRATION);
        if ($integrations) {
            foreach ($integrations as $integration) {
                $appsFlyerSettings = new AppsFlyerIntegrationSettings($integration->getIntegrationParams());
                if ($this->_isReady($appsFlyerSettings)) {
                    $appsFlyerSettings->setId($integration->getId());
                    $this->_api = new Api\Connect();
                    $this->_loadData($appsFlyerSettings);
                }
            }
        }
        return true;
    }
    private function _loadData(AppsFlyerIntegrationSettings $appsFlyerSettings)
    {
        $params = ["api_token" => $appsFlyerSettings->getApiToken(), "from" => (new \DateTime("-3 days"))->format("Y-m-d"), "to" => (new \DateTime("now"))->format("Y-m-d")];
        try {
            $csv = $this->_api->getAppsFlyerApi($appsFlyerSettings->getAppName(), $params);
            $csvData = $this->_parseCsv($csv);
            $conversionData = [];
            foreach ($csvData as $row) {
                $conversionDataRow = $this->_paramsToConversion($row);
                if (!empty($conversionDataRow)) {
                    $conversionData[] = $conversionDataRow;
                }
            }
            if (!empty($conversionData)) {
                \Component\Conversions\Service\ConversionsService::instance()->importArray($conversionData);
            }
            AppsFlyerLog::success($appsFlyerSettings);
            return true;
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            AppsFlyerLog::error($appsFlyerSettings, "third_party_integration.errors.token", $e->getMessage());
            return false;
        }
    }
    private function _parseCsv($csvData)
    {
        $result = [];
        $rows = explode("\n", $csvData);
        if (!count($rows)) {
            return $result;
        }
        $header = str_getcsv($rows[0]);
        if (count($rows) <= 2 && count($header) == 1) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("Suspicious reply: empty");
        }
        for ($i = 1; $i < count($rows); $i++) {
            $row = str_getcsv($rows[$i]);
            if (count($row) != 0) {
                if (count($header) != count($row)) {
                    \Traffic\Logging\Service\LoggerService::instance()->warning("Invalid row");
                } else {
                    $result[] = array_combine($header, $row);
                }
            }
        }
        return $result;
    }
    private function _paramsToConversion($data)
    {
        if ($data[APPSFLYER_SUBID] && $data[APPSFLYER_REVENUE]) {
            $conversion["subid"] = $data[APPSFLYER_SUBID];
            $conversion["payout"] = $data[APPSFLYER_REVENUE];
            $conversion["tid"] = $data[APPSFLYER_ID];
            $conversion["currency"] = CONVERSION_CURRENCY;
            $conversion["status"] = CONVERSION_STATUS;
            $conversion["from"] = CONVERSION_FROM;
            return $conversion;
        }
        return [];
    }
    private function _isReady(AppsFlyerIntegrationSettings $appsFlyerSettings)
    {
        if ($appsFlyerSettings->getLastUpdate()) {
            $diff = time() - $appsFlyerSettings->getLastUpdate();
            $updateTimeSecond = $appsFlyerSettings->getUpdateTime() * 60;
            if ($diff < $updateTimeSecond) {
                return false;
            }
        }
        return true;
    }
}

?>