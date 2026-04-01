<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

return ["conversions" => ["title" => "Import conversions", "log" => "Conversions", "result_message" => "Imported {{success}} out of {{total}}", "info" => "\n                <p>Row format <code>sub_id,payout,tid,status</code> (tid is optional, but should be specified to import additional sales).</p>\n                <p>Example, <code>1-2-2016020212312312,1.4,,sale</code></p>", "currency" => "Currency", "statuses" => ["lead" => "Lead", "sale" => "Sale", "rejected" => "Rejected", "rebill" => "Upsell"], "import" => "Import", "original_status" => "Original Status", "original_status_th" => "Orig. Status", "previous_status" => "Previous status", "conversion_id" => "Conv. ID", "tid" => "TID", "sub_id_empty" => "SubID empty", "sub_id_not_found" => "SubID not found \"%s\""]];

?>