<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback;

class Pipeline
{
    protected $_stages = NULL;
    public function __construct()
    {
        $this->_stages = [new Stages\BuildConversionStage(), new Stages\UpdateStatusesStage(), new Stages\UpdateRevenueStage(), new Stages\UpdateCostsStage(), new Stages\UpdateConversionParamsStage(), new Stages\UpdateClickParamsStage(), new SaveChangesStage(), new SyncConversionWithClickStage(), new Stages\SendPostbacksStage(), new Stages\UpdateConversionCapStage()];
    }
    public function process(\Component\Postback\Postback $postback)
    {
        $payloads = PayloadFactory::produce($postback);
        $errors = [];
        $conversions = [];
        foreach ($payloads as $payload) {
            try {
                \Core\Logging\Service\PostbackLoggerService::instance()->log($payload->getClickInfo());
                foreach ($this->_stages as $stage) {
                    $stage->process($payload);
                }
                \Core\Logging\Service\PostbackLoggerService::instance()->log("Subid " . $payload->getPostback()->getSubId() . " complete");
            } catch (\Exception $error) {
                $errors[] = $error->getMessage();
                $conversions[] = $payload->getConversionToSave();
            }
        }
        if (!empty($errors)) {
            throw new \Component\Postback\PostbackError(implode("\n", $errors));
        }
        return $conversions;
    }
}

?>