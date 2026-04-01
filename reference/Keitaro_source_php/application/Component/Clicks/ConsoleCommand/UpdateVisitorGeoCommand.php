<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Clicks\ConsoleCommand;

class UpdateVisitorGeoCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("try to update visitors with empty geo params")->setName("visitor:update_geo");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->checkEmptyVisitorGeo($output);
        $output->write("Done");
    }
    public function checkEmptyVisitorGeo(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $lastId = NULL;
        $rows = \Component\Clicks\Repository\VisitorRepository::instance()->getEmptyVisitorIds($lastId);
        while (!empty($rows)) {
            $extractor = new \Component\Clicks\ClickProcessing\ExtractReferences(true);
            $rawClicks = [];
            foreach ($rows as $row) {
                $ipString = long2ip((int) $row[\Component\Clicks\Repository\VisitorRepository::IP]);
                $info = \Traffic\Device\Service\IpInfoService::instance()->getIpInfo($ipString);
                $rawClicks[] = array_merge($info, ["id" => $row[\Component\Clicks\Repository\VisitorRepository::ID]]);
                $lastId = $row[\Component\Clicks\Repository\VisitorRepository::ID];
            }
            $rawClicks = $extractor->process($rawClicks);
            \Core\Db\Db::instance()->beginTransaction();
            foreach ($rawClicks as $rawClick) {
                if (!empty($rawClick["country_id"])) {
                    \Core\Db\Db::instance()->update(\Component\Clicks\Model\Visitor::getTableName(), "id = " . \Core\Db\Db::quote($rawClick["id"]), ["country_id" => $rawClick["country_id"], "region_id" => $rawClick["region_id"], "city_id" => $rawClick["city_id"]]);
                }
            }
            \Core\Db\Db::instance()->commit();
            $output->writeln("Processed " . count($rows));
            $rows = \Component\Clicks\Repository\VisitorRepository::instance()->getEmptyVisitorIds($lastId);
        }
        return 0;
    }
}

?>