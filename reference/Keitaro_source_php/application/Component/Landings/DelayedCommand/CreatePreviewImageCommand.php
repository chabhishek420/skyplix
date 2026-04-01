<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\DelayedCommand;

class CreatePreviewImageCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "preview_image";
    public function priority()
    {
        return 10;
    }
    public function process($entries)
    {
        foreach ($entries as $entry) {
            \Component\Landings\LocalFile\PreviewImageService::instance()->createPreview($entry["domain"], $entry["systemPath"]);
        }
    }
    public static function enqueue($domain, $systemPath)
    {
        $payload = ["domain" => $domain, "systemPath" => $systemPath];
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $payload, \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
}

?>