<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\BackCompatibility;

class BackCompatibility
{
    protected static $_enabled = NULL;
    public static function init()
    {
        self::$_enabled = true;
        $loader = (include ROOT . "/vendor/autoload.php");
        $loader->addClassMap(["Component\\Streams\\Model\\BaseStream" => ROOT . "/application/Traffic/BackCompatibility/classes/BaseStream.php", "Component\\Clicks\\Model\\RawClick" => ROOT . "/application/Traffic/BackCompatibility/classes/RawClick.php", "Component\\StreamActions\\AbstractAction" => ROOT . "/application/Traffic/BackCompatibility/classes/AbstractAction.php", "Component\\Macros\\AbstractMacro" => ROOT . "/application/Traffic/BackCompatibility/classes/AbstractMacro.php", "Component\\Macros\\AbstractClickMacro" => ROOT . "/application/Traffic/BackCompatibility/classes/AbstractClickMacro.php", "Component\\Macros\\AbstractConversionMacro" => ROOT . "/application/Traffic/BackCompatibility/classes/AbstractConversionMacro.php"]);
    }
    public static function getMacroType($obj)
    {
        $type = NULL;
        if (self::$_enabled) {
            if ($obj instanceof \Component\Macros\AbstractClickMacro) {
                if (!$obj instanceof \Component\Macros\AbstractConversionMacro) {
                    $type = \Traffic\Macros\MacroRepository::CONVERSION;
                }
            } else {
                $type = \Traffic\Macros\MacroRepository::CLICK;
            }
        }
        return $type;
    }
    public static function isLegacyMacro($macro)
    {
        if (!self::$_enabled) {
            return false;
        }
        return $macro instanceof \Component\Macros\AbstractClickMacro || $macro instanceof \Component\Macros\AbstractConversionMacro;
    }
    public static function executeLegacyMacro($macro, \Core\Sandbox\SandboxContext $pageContext)
    {
        if ($macro instanceof \Component\Macros\AbstractClickMacro) {
            if ($macro instanceof \Component\Macros\AbstractConversionMacro) {
                throw new \Exception("Macro " . get_class($macro) . " is not compatible with current version of Keitaro");
            }
            $args = [$pageContext->stream(), $pageContext->conversion()];
        } else {
            $rawClick = new \Component\Clicks\Model\RawClick($pageContext->rawClick()->getData());
            $args = [$pageContext->stream(), $rawClick];
        }
        $params = array_merge($args, $pageContext->serverRequest()->getAllParams());
        return call_user_func_array([$macro, "process"], $params);
    }
    public static function isLegacyAction($action)
    {
        if (!self::$_enabled) {
            return false;
        }
        return $action instanceof \Component\StreamActions\AbstractAction;
    }
    public static function executeLegacyAction(\Component\StreamActions\AbstractAction $action, \Traffic\Pipeline\Payload $payload)
    {
        $stream = $payload->getStream();
        $rawClick = $payload->getRawClick();
        if (empty($stream)) {
            $stream = new \Traffic\Model\BaseStream();
        }
        $stream->setActionType($payload->getActionType());
        $stream->setActionPayload($payload->getActionPayload());
        $stream->setActionOptions($payload->getActionOptions());
        $destination = \Traffic\Actions\Service\StreamActionService::instance()->buildDestination($payload->getServerRequest(), $payload->getCampaign(), $stream->getActionPayload(), $stream, $rawClick);
        $rawClick->setDestination($destination);
        $rawClick = new \Component\Clicks\Model\RawClick($rawClick->getData());
        $stream = new \Component\Streams\Model\BaseStream();
        foreach ($rawClick->getData() as $field => $value) {
            if ($stream->hasField($field)) {
                $stream->set($field, $value);
            }
        }
        return $action->run($stream, $rawClick);
    }
}

?>