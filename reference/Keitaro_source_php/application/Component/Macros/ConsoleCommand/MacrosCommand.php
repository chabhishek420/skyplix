<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Macros\ConsoleCommand;

class MacrosCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Show available macros")->setName("macros");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $rows = [];
        $packs = ["For URLs" => \Traffic\Macros\MacroRepository::instance()->getClickMacros(), "For postbacks" => \Traffic\Macros\MacroRepository::instance()->getConversionMacros()];
        foreach ($packs as $pack => $macros) {
            foreach ($macros as $name => $macro) {
                if (!\Traffic\Macros\MacroRepository::instance()->isAlias($name)) {
                    $aliases = \Traffic\Macros\MacroRepository::instance()->getAliases($name);
                    $method = new \ReflectionMethod($macro, "process");
                    $params = $method->getParameters();
                    $params = array_splice($params, 2, 9);
                    $params = array_map(function ($param) {
                        return $param->name;
                    }, $params);
                    if (count($params)) {
                        $name .= ":" . implode(",", $params);
                    }
                    $row = ["{" . $name . "}"];
                    if (count($aliases)) {
                        $row[] = implode(",", $aliases);
                    }
                    $rows[$pack][] = $row;
                }
            }
            sort($rows[$pack]);
        }
        foreach ($rows as $pack => $items) {
            $output->writeln($pack);
            $table = new \Symfony\Component\Console\Helper\Table($output);
            $table->setHeaders(["Macro", "Aliases", "Description"])->setRows($items);
            $table->render();
        }
        return 0;
    }
}

?>