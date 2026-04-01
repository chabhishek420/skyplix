<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\ProcessPostback;

class SyncConversionWithClickStage implements StageInterface
{
    public function process(Payload $payload)
    {
        if ($payload->getPostback()->isIgnore()) {
            return NULL;
        }
        $this->_updateParamsFromClick($payload);
        $this->_saveConversion($payload);
    }
    private function _updateParamsFromClick(Payload $payload)
    {
        $conversion = $payload->getConversionToSave();
        $click = $payload->getClick();
        $fields = $this->_getCommonFields();
        foreach ($fields as $field) {
            $conversion->set($field, $click->get($field));
        }
    }
    protected function _getCommonFields()
    {
        $fields1 = array_keys(\Traffic\Model\Conversion::getFields());
        $fields2 = array_keys(\Traffic\Model\Click::getFields());
        return array_intersect($fields1, $fields2);
    }
    private function _saveConversion(Payload $payload)
    {
        $conversion = $payload->getConversionToSave();
        $conversion = \Traffic\Repository\ParameterRepository::instance()->filterConversion($conversion);
        try {
            $conversion->save();
        } catch (\ADODB_Exception $e) {
            if (strstr($e->getMessage(), "Duplicate")) {
                throw new \Component\Postback\PostbackError("Conversion by pair \"SubId + tid\" is already exists. Postback ignored.");
            }
            throw $e;
        }
    }
}

?>