<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Json;

class AbstractSerializer implements SerializerInterface
{
    protected $_fields = NULL;
    public function prepare($payload)
    {
    }
    public function serialize($obj, $exclusions = [])
    {
        if (!isset($this->_fields)) {
            throw new \Exception("Serializer does not have fields");
        }
        if (is_array($obj) || empty($obj)) {
            $data = $obj;
        } else {
            $data = $obj->getData();
        }
        $data = $this->_onlyFields($data, $this->_fields);
        $data = $this->extra($obj, $data);
        if (count($exclusions)) {
            foreach ($exclusions as $key) {
                unset($data[$key]);
            }
        }
        return $data;
    }
    public function extra($obj, $data)
    {
        return $data;
    }
    protected function _onlyFields($fullData, $fields)
    {
        $data = [];
        if (!is_bool($fields)) {
            foreach ($fields as $key) {
                $data[$key] = isset($fullData[$key]) ? $fullData[$key] : NULL;
            }
        } else {
            $data = $fullData;
        }
        return $data;
    }
    protected function _flatTimestamps($data)
    {
        $check = ["created_at", "updated_at"];
        foreach ($check as $key) {
            if (!empty($data[$key]) && $data[$key] instanceof \DateTime) {
                $data[$key] = $data[$key]->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
            }
        }
        return $data;
    }
}

?>