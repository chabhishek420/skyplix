<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Serializer;

class LandingSerializer extends \Core\Json\AbstractSerializer
{
    protected $_fields = true;
    private $_withGroupName = NULL;
    public function __construct($withGroupName = false)
    {
        $this->_withGroupName = $withGroupName;
    }
    public function extra($obj, $data)
    {
        $data = $this->_flatTimestamps($data);
        if (empty($obj)) {
            throw new \Exception("Empty obj");
        }
        if (!$this->_withGroupName) {
            unset($data["group"]);
        }
        if ($obj->getActionType() == "local_file") {
            $data = \Component\Landings\Service\LandingService::instance()->addPreviewData($obj, $data);
        }
        return $data;
    }
}

?>