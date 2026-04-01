<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Pipeline\Stage;

class UpdateHitLimitStage implements StageInterface
{
    const LIMIT = "limit";
    public function process(\Traffic\Pipeline\Payload $payload, \Traffic\Logging\TrafficLogEntry $logEntry)
    {
        $stream = $payload->getStream();
        $rawClick = $payload->getRawClick();
        if (empty($rawClick)) {
            throw new StageException("Empty rawClick");
        }
        if ($stream && $this->hasLimitFilter($stream)) {
            \Traffic\HitLimit\Service\HitLimitService::instance()->store($stream, $rawClick->getDateTime());
        }
        return $payload;
    }
    public function hasLimitFilter(\Traffic\Model\BaseStream $stream)
    {
        $filters = \Traffic\Repository\CachedStreamFilterRepository::instance()->allCached($stream);
        if ($filters && is_array($filters)) {
            foreach ($filters as $filter) {
                if ($filter->getName() == LIMIT) {
                    return true;
                }
            }
        }
        return false;
    }
}

?>