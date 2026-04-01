<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\GeoDb\Serializer;

class GeoDbSerializer implements \Core\Json\SerializerInterface
{
    protected $_fields = true;
    private $_checkUpdates = NULL;
    public function __construct($checkUpdates = true)
    {
        $this->_checkUpdates = $checkUpdates;
    }
    public function prepare($payload)
    {
    }
    public function serialize($db, $exclusions = [])
    {
        $definition = $db->definition();
        $manager = $db->manager();
        $status = $manager->status();
        $item = ["id" => $definition->id(), "name" => $definition->name(), "type" => $definition->type(), "exists" => $db->exists(), "path" => $definition->filePath(), "data_types" => $definition->dataTypes(), "status_code" => $status[0], "status_text" => $status[1], "time" => $db->exists() ? $manager->timestamp()->format(\Core\Model\AbstractModel::DATETIME_FORMAT) : NULL, "is_recommended" => $definition->isRecommended(), "setting_key" => $definition->settingKey(), "purchase_link" => $definition->purchaseLink(), "key" => $definition->settingKey() ? \Traffic\Repository\CachedSettingsRepository::instance()->get($definition->settingKey()) : NULL];
        try {
            $item["update_available"] = $this->_checkUpdates ? $manager->isUpdateAvailable() : NULL;
        } catch (\Component\GeoDb\Error\DbError $e) {
            $item["error"] = $e->getMessage();
            return $item;
        }
    }
}

?>