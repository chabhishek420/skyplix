<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\System\ConsoleCommand;

class DeleteAllStatsCommand extends \Symfony\Component\Console\Command\Command
{
    protected function configure()
    {
        $this->setDescription("Clear disk space by deleting stats")->setName("system:delete_all_stats")->addOption("click-remove-till-date", NULL, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, "Will remove clicks till specified date (format YYYY-MM-DD)", (new \DateTime())->format("Y-m-d"))->addOption("truncate-all-stats", "t", \Symfony\Component\Console\Input\InputOption::VALUE_NONE, "Will remove all current clicks stats");
    }
    protected function execute(\Symfony\Component\Console\Input\InputInterface $input, \Symfony\Component\Console\Output\OutputInterface $output)
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);
        $warningText = "Important! The following will happen:\n- old versions tables removal,\n- clicks stats to the exact date removal (if specified in parameters),\n- optimization of the clicks in the stats,\n- cleaning all the current stats completely (if specified in parameters).\n\nThese inquiries will block the database\nduring the implementation and require additional free space on a disk.";
        $io->warning($warningText);
        if (!$io->confirm("Execute the command?")) {
            $io->warning("Not confirmed, exiting.");
            return 1;
        }
        $io->section("Removing old versions tables");
        $dbUtil = new \Component\System\DatabaseUtil\DatabaseUtil();
        $this->_removeOldVersionsTables($dbUtil);
        $io->success("Old versions tables removed");
        $io->section("Removing clicks stats to the exact date");
        $removeClickTillDateValue = $input->getOption("click-remove-till-date");
        if ($removeClickTillDateValue) {
            try {
                $this->_removeClicksStats($removeClickTillDateValue);
                $io->success("Clicks stats to the exact date removed");
            } catch (\Exception $e) {
                $io->error("Error while deleting clicks stats: " . $e->getMessage());
                return 1;
            }
        }
        $io->warning("Date to remove is not specified, skipping");
        $io->section("Optimizing clicks tables");
        $this->_optimizeClicksTables($dbUtil);
        $io->success("Clicks tables optimized");
        $io->section("Clearing all current stats");
        $isTruncateAllStats = $input->getOption("truncate-all-stats");
        if ($isTruncateAllStats) {
            try {
                $this->_removeAllStats($dbUtil);
            } catch (\Exception $e) {
                $io->error("Error while deleting all current stats: " . $e->getMessage());
                return 1;
            }
        }
        $io->warning("Option not specified, skipping");
        $io->success("All current stats cleared");
        return 0;
    }
    private function _removeOldVersionsTables(\Component\System\DatabaseUtil\DatabaseUtil $dbUtil)
    {
        $tablesToRemove = array_filter($dbUtil->getTables(), function ($tableName) {
            return strpos($tableName, "keitaro_archive") !== false || strpos($tableName, "keitaro_index") !== false;
        });
        foreach ($tablesToRemove as $table) {
            $dbUtil->truncateTable($table);
        }
    }
    private function _removeClicksStats($removeClickTillDateValue)
    {
        $removeClickTillDate = \DateTime::createFromFormat("%Y-%m-%d", $removeClickTillDateValue);
        if (!$removeClickTillDateValue) {
            throw new \Exception("Cannot recognize date format, must be YYYY-MM-DD");
        }
        $where = "datetime < \"" . \Core\Db\Db::quote($removeClickTillDate) . "\"";
        \Core\Db\DataService::instance()->deleteAll(\Traffic\Model\Click::definition(), $where);
    }
    private function _optimizeClicksTables(\Component\System\DatabaseUtil\DatabaseUtil $dbUtil)
    {
        $tablesToOptimize = ["keitaro_clicks", "keitaro_conversions", "keitaro_visitors", "keitaro_ref_ips", "keitaro_ref_referrers", "keitaro_ref_user_agents"];
        foreach ($tablesToOptimize as $table) {
            $dbUtil->optimizeTable($table);
        }
    }
    private function _removeAllStats(\Component\System\DatabaseUtil\DatabaseUtil $dbUtil)
    {
        $tablesToTruncate = ["keitaro_ip_sessions", "keitaro_clicks", "keitaro_visitors"];
        foreach ($tablesToTruncate as $table) {
            $dbUtil->truncateTable($table);
        }
    }
}

?>