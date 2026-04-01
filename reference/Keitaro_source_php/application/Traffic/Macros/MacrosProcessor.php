<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Macros;

class MacrosProcessor
{
    public static function process(\Core\Sandbox\SandboxContext $pageContext, $content)
    {
        $processor = new MacrosProcessor();
        return $processor->processContent($content, $pageContext);
    }
    public function processContent($content, \Core\Sandbox\SandboxContext $pageContext)
    {
        if (empty($pageContext)) {
            throw new \Exception("pageContext is not defined");
        }
        if (!strstr($content, "\$") && !strstr($content, "{")) {
            return $content;
        }
        $params = $pageContext->serverRequest()->getAllParams();
        $params = $this->_addParamsFromCampaign($params, $pageContext);
        $parserItems = $this->_parseForMacros($content);
        foreach ($parserItems as $parserItem) {
            $value = $this->_searchInMacroScripts($parserItem, $pageContext);
            if (!is_null($value)) {
                $content = $this->_replace($content, $parserItem, $value);
            } else {
                $value = $this->_searchInParams($parserItem, $params);
                if (!is_null($value)) {
                    $content = $this->_replace($content, $parserItem, $value);
                }
            }
        }
        return $content;
    }
    private function _parseForMacros($content)
    {
        $patterns = ["/{(_?)([a-z0-9_\\-]+):?([^{^}]*?)}/i", "/\\\$(_?)([a-z0-9_-]+)/i"];
        $items = [];
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[0] as $n => $originalSubString) {
                    $rawMode = $matches[1][$n];
                    $name = $matches[2][$n];
                    $args = isset($matches[3]) ? $matches[3][$n] : "";
                    $args = explode(",", $args);
                    $items[] = new ParserItem($name, $originalSubString, $rawMode, $args);
                }
            }
        }
        return $items;
    }
    private function _addParamsFromCampaign($params, \Core\Sandbox\SandboxContext $pageContext)
    {
        $campaign = $pageContext->campaign();
        if (empty($campaign)) {
            return $params;
        }
        $campaignParameters = $campaign->getParameters();
        if (empty($campaignParameters)) {
            return $params;
        }
        foreach ($campaignParameters as $param => $paramInfo) {
            $name = NULL;
            if (!empty($paramInfo["name"])) {
                $name = $paramInfo["name"];
            }
            if (!empty($paramInfo["param"])) {
                $name = $paramInfo["param"];
            }
            if (!empty($name)) {
                $params[$name] = $pageContext->rawClick()->get($param);
            }
        }
        return $params;
    }
    private function _searchInMacroScripts(ParserItem $parserItem, \Core\Sandbox\SandboxContext $pageContext)
    {
        $macro = MacroRepository::instance()->getMacro($parserItem->name());
        if (empty($macro)) {
            return NULL;
        }
        if (\Traffic\BackCompatibility\BackCompatibility::isLegacyMacro($macro)) {
            return \Traffic\BackCompatibility\BackCompatibility::executeLegacyMacro($macro, $pageContext);
        }
        if ($macro instanceof AbstractConversionMacro) {
            if ($macro instanceof AbstractClickMacro) {
                throw new \Exception("Incorrect Macro type " . get_class($macro));
            }
            if (!$pageContext->rawClick()) {
                throw new \Exception("pageContext must contain rawClick");
            }
            $macrosArgs = [$pageContext->stream(), $pageContext->rawClick()];
        } else {
            if (!$pageContext->conversion()) {
                return "{" . $parserItem->name() . "}";
            }
            $macrosArgs = [$pageContext->stream(), $pageContext->conversion()];
        }
        $macro->setServerRequest($pageContext->serverRequest());
        if ($macro->alwaysRaw()) {
            $parserItem->setRawMode(true);
        }
        $macrosArgs = array_merge($macrosArgs, $parserItem->arguments());
        $value = call_user_func_array([$macro, "process"], $macrosArgs);
        if (is_null($value)) {
            $parserItem->setRawMode(true);
            return $parserItem->originalString();
        }
        return $value;
    }
    private function _searchInParams(ParserItem $parserItem, $params)
    {
        if (empty($params)) {
            return NULL;
        }
        if (!array_key_exists($parserItem->name(), $params)) {
            return NULL;
        }
        $value = $params[$parserItem->name()];
        if (is_array($value)) {
            $value = json_encode($value);
        }
        return $value;
    }
    private function _replace($content, ParserItem $parserItem, $value)
    {
        if (!$parserItem->rawMode()) {
            $value = urlencode($value);
        }
        $content = str_replace($parserItem->originalString(), $value, $content);
        return $content;
    }
}

?>