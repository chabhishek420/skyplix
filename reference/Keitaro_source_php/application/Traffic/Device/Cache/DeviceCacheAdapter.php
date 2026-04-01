<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Device\Cache;

class DeviceCacheAdapter implements \DeviceDetector\Cache\CacheInterface
{
    private $_provider = NULL;
    public function __construct(\Doctrine\Common\Cache\CacheProvider $provider)
    {
        $this->_provider = $provider;
    }
    public function fetch($id)
    {
        $this->_provider->fetch($id);
    }
    public function contains($contains, $id)
    {
        return $this->_provider->contains($id);
    }
    public function save($save, $id, $data = 0, string $lifeTime)
    {
        return $this->_provider->save($id, $data, $lifeTime);
    }
    public function delete($delete, $id)
    {
        return $this->_provider->delete($id);
    }
    public function flushAll($flushAll)
    {
        return $this->_provider->flushAll();
    }
}

?>