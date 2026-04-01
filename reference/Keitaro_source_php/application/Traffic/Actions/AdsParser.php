<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions;

class AdsParser
{
    private $_content = NULL;
    private $_cid = NULL;
    public function __construct($content, $cid)
    {
        $this->_content = $content;
        $this->_cid = urlencode($cid);
    }
    public function getCode($wrap = true)
    {
        if (preg_match("/script[^>]+src/i", $this->_content)) {
            $script = $this->_generateMultiCode($this->_content);
        } else {
            $script = $this->_generateHtml($this->_content);
        }
        if ($wrap) {
            $script = $this->_wrap($script);
        }
        return $script;
    }
    private function _generateHtml($code)
    {
        return "var newNode = d.createElement(\"span\");newNode.innerHTML = " . json_encode($code) . ";" . "d.getElementById('" . $this->_cid . "').appendChild(newNode);";
    }
    private function _generateMultiCode($code)
    {
        $parts = [];
        $this->_removeComments($code, $parts);
        $this->_parseScriptSrc($code, $parts);
        $this->_parseScriptOtherScript($code, $parts);
        $code = trim($code);
        if (!empty($code)) {
            array_unshift($parts, $this->_generateHtml($code));
        }
        return implode("", $parts);
    }
    private function _removeComments($code, $parts)
    {
        $code = preg_replace("/\\s*<!--.*?-->\\s*/si", "", $code);
        $code = preg_replace("/\\s*\\/\\*.+?\\*\\/\\s*/si", "", $code);
    }
    private function _parseScriptSrc($code, $parts)
    {
        $pattern = "/<script[^>]+src=['\"](.*?)['\"][^>]*><\\/script>/si";
        if (preg_match_all($pattern, $code, $result)) {
            foreach ($result[1] as $item) {
                $parts[] = "var s=d.createElement('script');s.src='" . $item . "';" . "d.getElementById('" . $this->_cid . "').appendChild(s);";
            }
            $code = str_replace($result[0], "", $code);
        }
    }
    private function _parseScriptOtherScript($code, $parts)
    {
        $pattern = "/<script[^>]+>(.+?)<\\/script>/si";
        if (preg_match_all($pattern, $code, $result)) {
            foreach ($result[1] as $item) {
                $part = "var s=d.createElement('script');s.innerHTML=" . json_encode($item) . ";" . "d.getElementById('" . $this->_cid . "').appendChild(s);";
                array_unshift($parts, $part);
            }
            $code = str_replace($result[0], "", $code);
        }
    }
    private function _wrap($code)
    {
        $parts[] = "var old = d.write;\n        d.write = function( content ) {\n            if (\"interactive\" === document.readyState) {\n               var newNode = d.createElement(\"span\");\n               newNode.innerHTML = content;\n               var el = d.getElementById('" . $this->_cid . "');\n               el.parentNode.insertBefore(newNode, el);\n               return;\n            }\n            old.call( document, content );\n        };";
        $code .= $parts;
        return "var d=document;" . $code . "";
    }
}

?>