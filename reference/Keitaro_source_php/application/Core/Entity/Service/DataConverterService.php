<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Entity\Service;

class DataConverterService extends \Traffic\Service\AbstractService
{
    public function prepareManyForMysql($fields, $rows)
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->prepareForMysql($fields, $row);
        }
        return $result;
    }
    public function prepareForMysql($fields, $data)
    {
        $newData = [];
        if (empty($fields)) {
            throw new \Exception("\$fields is empty");
        }
        foreach ($fields as $field => $type) {
            if ($type !== \Core\Type\Type::VIRTUAL) {
                if (array_key_exists($field, $data)) {
                    if (!array_key_exists($field, $data)) {
                        $data[$field] = NULL;
                    }
                    $newData[$field] = $this->convertToType($data[$field], $type, NULL);
                }
            }
        }
        unset($data);
        return $newData;
    }
    public function restoreFromMysql($fields, $data)
    {
        $newData = [];
        foreach ($fields as $field => $type) {
            if (!isset($data[$field])) {
                $newData[$field] = NULL;
            } else {
                switch ($type) {
                    case \Core\Type\Type::BOOLEAN:
                        $newData[$field] = $data[$field];
                        break;
                    case \Core\Type\Type::INTEGER:
                        $newData[$field] = (int) $data[$field];
                        break;
                    case \Core\Type\Type::DATE:
                    case \Core\Type\Type::DATETIME:
                        if (is_string($data[$field])) {
                            $newData[$field] = new \DateTime($data[$field]);
                        } else {
                            $newData[$field] = $data[$field];
                        }
                        break;
                    case \Core\Type\Type::JSON:
                        if (is_string($data[$field])) {
                            $newData[$field] = json_decode($data[$field], true);
                        }
                        break;
                    default:
                        $newData[$field] = $data[$field];
                }
            }
        }
        return $newData;
    }
    public function convertToType($value, $type, $adapter)
    {
        switch ($type) {
            case \Core\Type\Type::BOOLEAN:
                if (is_int($value)) {
                    return $value;
                }
                return $value ? 1 : 0;
                break;
            case \Core\Type\Type::INTEGER:
                if (is_int($value)) {
                    return $value;
                }
                return (int) $value;
                break;
            case \Core\Type\Type::DECIMAL:
                if (is_double($value) || is_int($value)) {
                    return $value;
                }
                return (int) $value;
                break;
            case \Core\Type\Type::DATE:
            case \Core\Type\Type::DATETIME:
                return $this->convertDateForMysql($value);
                break;
            case \Core\Type\Type::JSON:
                return json_encode($value);
                break;
            default:
                if (is_string($value)) {
                    return $value;
                }
                return (int) $value;
        }
    }
    public function convertDateForMysql($value)
    {
        if ($value instanceof \DateTime) {
            if (is_array($value)) {
                return $value;
            }
            return (new \DateTime($value["date"]))->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
        }
        $value->setTimezone(new \DateTimeZone("UTC"));
        return $value->format(\Core\Model\AbstractModel::DATETIME_FORMAT);
    }
}

?>