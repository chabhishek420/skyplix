<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback;

class SaveChangesStage implements StageInterface
{
    public function process(Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_saveConversion($payload);
        $this->_saveClick($payload);
    }
    private function _saveConversion(Payload $payload)
    {
        $conversion = $payload->getConversionToSave();
        $conversion = \Traffic\Repository\ParameterRepository::instance()->filterConversion($conversion);
        try {
            $conversion->save();
        } catch (ADODB_Exception $e) {
            if (strstr($e->getMessage(), "Duplicate")) {
                throw new \Component\Postback\PostbackError("Conversion by pair \"SubId + tid\" is already exists. Postback ignored.");
            }
            throw $e;
        }
    }
    private function _saveClick(Payload $payload)
    {
        $click = $payload->getClick();
        $click = \Traffic\Repository\ParameterRepository::instance()->filterClick($click);
        $click->save();
    }
}

?>