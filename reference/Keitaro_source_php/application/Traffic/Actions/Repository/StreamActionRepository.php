<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Repository;

class StreamActionRepository extends \Traffic\Repository\AbstractBaseRepository
{
    private $_actions = [];
    private $_aliases = [];
    private $_exclude = ["example", "build_html", "sub_id"];
    const BUILD_HTML = "build_html";
    const DEFAULT_CUSTOM_PATH = "/application/redirects";
    public function __construct($customPath = NULL)
    {
        $this->_registerBuiltInActions();
        if (empty($customPath)) {
            $customPath = ROOT . DEFAULT_CUSTOM_PATH;
        }
        if (!\Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::HIDE_CLICK_KEY)) {
            $this->_exclude[] = "hide_click_detect";
        }
        $this->loadCustomActions($customPath);
    }
    public function loadCustomActions($customPath)
    {
        $customActionArray = \Component\StreamActions\Repository\CustomStreamActionRepository::instance()->getCustomStreamActions($customPath, $this->_actions);
        foreach ($customActionArray as $name => $redirect) {
            $this->register($name, $redirect);
        }
        $this->_sort();
        if (empty($this->_actions["location"])) {
            $this->alias("location", "http");
        }
    }
    private function _registerBuiltInActions()
    {
        $this->register("blank_referrer", new \Traffic\Actions\Predefined\BlankReferrer());
        $this->register("show_html", new \Traffic\Actions\Predefined\ShowHtml());
        $this->alias("build_html", "show_html");
        $this->alias("return_html", "show_html");
        $this->register("campaign", new \Traffic\Actions\Predefined\ToCampaign());
        $this->alias("group", "campaign");
        $this->register("curl", new \Traffic\Actions\Predefined\Curl());
        $this->register("double_meta", new \Traffic\Actions\Predefined\DoubleMeta());
        $this->register("show_text", new \Traffic\Actions\Predefined\ShowText());
        $this->alias("echo", "show_text");
        $this->register("formsubmit", new \Traffic\Actions\Predefined\FormSubmit());
        $this->register("frame", new \Traffic\Actions\Predefined\Frame());
        $this->register("iframe", new \Traffic\Actions\Predefined\Iframe());
        $this->register("http", new \Traffic\Actions\Predefined\HttpRedirect());
        $this->register("js", new \Traffic\Actions\Predefined\Js());
        $this->register("js_for_iframe", new \Traffic\Actions\Predefined\JsForIframe());
        $this->register("js_for_script", new \Traffic\Actions\Predefined\JsForScript());
        $this->register("meta", new \Traffic\Actions\Predefined\Meta());
        $this->register("remote", new \Traffic\Actions\Predefined\Remote());
        $this->register("status404", new \Traffic\Actions\Predefined\Status404());
        $this->register("sub_id", new \Traffic\Actions\Predefined\SubId());
        $this->register("do_nothing", new \Traffic\Actions\Predefined\DoNothing());
        $this->register("local_file", new \Traffic\Actions\Predefined\LocalFile());
    }
    public function register($name, $obj)
    {
        if (!empty($this->_actions[$name])) {
            \Traffic\Logging\Service\LoggerService::instance()->error("Redirect " . $name . " is already exists. Please remove or rename the file redirects/" . $name . ".php.");
        } else {
            $this->_actions[$name] = $obj;
        }
    }
    public function alias($aliasName, $redirectName)
    {
        if (empty($this->_actions[$redirectName])) {
            throw new \Traffic\Actions\ActionError("Action " . $redirectName . " is not defined");
        }
        if (!empty($this->_actions[$aliasName])) {
            throw new \Traffic\Actions\ActionError("Action " . $aliasName . " is already defined");
        }
        $this->_aliases[] = $aliasName;
        $this->_actions[$aliasName] = $this->_actions[$redirectName];
    }
    public function isRedirect($name)
    {
        return isset($this->_actions[$name]) && $this->_actions[$name]->getType() == \Traffic\Actions\AbstractAction::TYPE_REDIRECT;
    }
    public function getNewActionInstance($name)
    {
        if (empty($this->_actions[$name])) {
            throw new \Traffic\Actions\ActionError("Redirect '" . $name . "' is not defined");
        }
        return clone $this->_actions[$name];
    }
    public function getActions()
    {
        return $this->_actions;
    }
    public function getNames()
    {
        return array_keys($this->_actions);
    }
    public function getListAsOptions()
    {
        $options = [];
        foreach ($this->_actions as $key => $action) {
            if (!in_array($key, $this->_aliases) && !in_array($key, $this->_exclude)) {
                if (\Core\Locale\LocaleService::instance()->exists("stream_actions.action_descriptions." . $key)) {
                    $description = \Core\Locale\LocaleService::t("stream_actions.action_descriptions." . $key);
                } else {
                    $description = "";
                }
                $options[] = ["key" => $key, "name" => $action->getName(), "field" => $action->getField(), "type" => $action->getType(), "description" => $description];
            }
        }
        return $options;
    }
    private function _sort()
    {
        $sortCmp = function ($el1, $el2) {
            return $el2->getWeight() < $el1->getWeight() ? 1 : -1;
        };
        uasort($this->_actions, $sortCmp);
    }
}

?>