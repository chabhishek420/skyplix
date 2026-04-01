<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Service;

class RedirectService extends \Traffic\Service\AbstractService
{
    public function scriptRedirect($url)
    {
        return "function process() {\n                window.location = \"" . $url . "\";\n            }\n            window.onerror = process;\n            process();\n        ";
    }
    public function frameRedirect($url)
    {
        return "<script type=\"application/javascript\">\n            function process() {\n                top.location = \"" . $url . "\";\n            }\n\n            window.onerror = process;\n\n            if (top.location.href != window.location.href) {\n                process()\n            }\n        </script>";
    }
    public function metaRedirect($url, $options = [])
    {
        $defaultOptions = ["delay" => 1, "no_referrer" => false];
        $options = array_merge($defaultOptions, $options);
        $metas = ["<meta http-equiv=\"REFRESH\" content=\"" . $options["delay"] . "; URL='" . $url . "'\">"];
        if ($options["no_referrer"]) {
            $metas[] = "<meta name=\"referrer\" content=\"no-referrer\" />";
        }
        $metas = implode("\n    ", $metas);
        return "<html lang=\"en\">\n  <head>\n    " . $metas . "\n    <title></title>\n  </head>\n</html>";
    }
}

?>