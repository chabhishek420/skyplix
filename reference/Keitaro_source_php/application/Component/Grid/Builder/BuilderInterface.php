<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Builder;

final class BuilderInterface
{
    public abstract function getSummary();
    public abstract function getQuery();
    public abstract function getDecorator();
    public abstract function getColumns();
    public abstract function getRange();
    public abstract function eachChunk($chunkSize, $callback);
    public abstract function getDefinition();
    public abstract function fetchRows();
}

?>