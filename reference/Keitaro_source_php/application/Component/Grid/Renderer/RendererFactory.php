<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Grid\Renderer;

class RendererFactory
{
    public static function get($format, \Component\Grid\Builder\BuilderInterface $builder)
    {
        switch ($format) {
            case HtmlRenderer::FORMAT:
                return new HtmlRenderer($builder);
                break;
            case CsvRenderer::FORMAT:
                return new CsvRenderer($builder);
                break;
            case JsonRenderer::FORMAT:
            default:
                return new JsonRenderer($builder);
        }
    }
}

?>