<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Conversions\Service;

class ConversionsService extends \Traffic\Service\AbstractService
{
    const SUB_ID_NAME = "sub_id";
    const REVENUE_NAME = "revenue";
    const TID_NAME = "tid";
    const STATUS_NAME = "status";
    public function getSubIdAliases()
    {
        return ["trackID", "subid", "sub_id", "subaccount", "aff_sub", "clickid", "subId", "SubID", "trackingCodes"];
    }
    public function processEntries($entries, $conversionCurrency)
    {
        $settingsCurrency = \Traffic\Repository\CachedSettingsRepository::instance()->get("currency", "USD");
        $rows = explode("\n", $entries);
        $result = [];
        foreach ($rows as $row) {
            $params = explode(",", $row);
            if (isset($params[0]) && isset($params[1])) {
                $conversion = [];
                $conversion[SUB_ID_NAME] = trim($params[0]);
                $revenue = trim($params[1]);
                $convertedRevenue = \Core\Currency\Service\CurrencyService::instance()->exchange($revenue, $conversionCurrency, $settingsCurrency);
                $conversion[REVENUE_NAME] = $convertedRevenue;
                if (isset($params[2])) {
                    $conversion[TID_NAME] = trim($params[2]);
                }
                if (isset($params[3])) {
                    $conversion[STATUS_NAME] = trim($params[3]);
                }
                $result[] = $conversion;
            }
        }
        return $result;
    }
    public function import($data, $conversionCurrency)
    {
        $entries = $this->processEntries($data, $conversionCurrency);
        return $this->importArray($entries);
    }
    public function importArray($entries)
    {
        $good = 0;
        $total = count($entries);
        $errors = [];
        $pipeline = new \Component\Postback\ProcessPostback\Pipeline();
        foreach ($entries as $entry) {
            try {
                $postback = \Component\Postback\Postback::buildFromParams($entry);
                $pipeline->process($postback);
                $good++;
            } catch (\Component\Postback\PostbackError $e) {
                $errors[] = $e->getMessage();
            } catch (\Core\Exceptions\NotFoundError $e) {
                $errors[] = $entry[SUB_ID_NAME] . ": " . $e->getMessage();
            }
        }
        $result = ["good" => $good, "total" => $total, "errors" => $errors];
        return $result;
    }
}

?>