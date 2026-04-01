<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Postback\DelayedCommand;

class ProcessPostbackCommand implements \Component\DelayedCommands\DelayedCommandInterface
{
    const NAME = "process_postback";
    public function priority()
    {
        return 3;
    }
    public function process($entries)
    {
        $case = new \Component\Postback\ProcessPostback\Pipeline();
        foreach ($entries as $postbackData) {
            try {
                $postback = new \Component\Postback\Postback($postbackData);
                $case->process($postback);
            } catch (\Component\Postback\PostbackError $error) {
                \Core\Logging\Service\PostbackLoggerService::instance()->log($error->getMessage());
            }
        }
    }
    public static function processPostback(\Component\Postback\Postback $postback)
    {
        $command = [\Traffic\CommandQueue\Service\DelayedCommandService::PAYLOAD => $postback->serialize(), \Traffic\CommandQueue\Service\DelayedCommandService::COMMAND => NAME];
        \Traffic\CommandQueue\Service\DelayedCommandService::instance()->push($command);
    }
}

?>