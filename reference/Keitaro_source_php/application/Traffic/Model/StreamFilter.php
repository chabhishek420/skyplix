<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Model;

class StreamFilter extends \Core\Model\AbstractModel implements \Core\Entity\Model\EntityModelInterface
{
    private $_cachedStream = NULL;
    protected static $_tableName = "stream_filters";
    protected static $_entityName = "stream_filters";
    protected static $_fields = NULL;
    const REJECT = "reject";
    const ACCEPT = "accept";
    public static function validator()
    {
        return new \Component\StreamFilters\Validator\StreamFilterValidator();
    }
    public static function repository()
    {
        return \Component\StreamFilters\Repository\StreamFilterRepository::instance();
    }
    public static function service()
    {
        return \Component\StreamFilters\Service\StreamFilterService::instance();
    }
    public function setStream(BaseStream $stream)
    {
        $this->_cachedStream = $stream;
        $this->_data["stream_id"] = $stream->getId();
        return $this;
    }
    public function getStreamId()
    {
        return $this->get("stream_id");
    }
    public function getPayload()
    {
        return $this->get("payload");
    }
    public function getName()
    {
        return $this->get("name");
    }
    public function setStreamId($id)
    {
        $this->set("stream_id", $id);
        return $this;
    }
    public function getMode()
    {
        return $this->get("mode");
    }
}

?>