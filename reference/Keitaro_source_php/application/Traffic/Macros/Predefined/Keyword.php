<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros\Predefined;

class Keyword extends \Traffic\Macros\AbstractClickMacro
{
    private $_defaultCharset = NULL;
    const UTF8 = "utf-8";
    const CP1251 = "cp1251";
    public function __construct($defaultCharset)
    {
        $this->_defaultCharset = $defaultCharset;
    }
    public function process(\Traffic\Model\BaseStream $stream, \Traffic\RawClick $rawClick, $charset = NULL)
    {
        if (empty($charset)) {
            $charset = $this->_defaultCharset;
        }
        $keyword = $rawClick->getKeyword();
        if (!empty($charset) && $charset != UTF8) {
            $keyword = iconv("utf-8", $charset, $keyword);
        }
        $keyword ? exit : "";
    }
}

?>