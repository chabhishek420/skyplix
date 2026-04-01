<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark;

class Benchmark
{
    private $_output = NULL;
    public function __construct(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->_output = $output;
    }
    public function init()
    {
        if (!defined("ENV")) {
            define("ENV", "testing");
        }
        if (!\Core\Application\Application::instance()->isTesting()) {
            throw new \Core\Exception("Benchmark must work in testing env only!");
        }
        \Traffic\Service\SettingsService::instance()->updateValues(["geodb" => "ip2location_lite"]);
    }
    public function clearTable()
    {
        if (\Core\Application\Application::instance()->isTesting()) {
            $res = \Core\Db\Db::instance()->execute("SHOW TABLES");
            \Core\Db\Db::instance()->execute("SET FOREIGN_KEY_CHECKS=0");
            foreach ($res as $table) {
                $table = array_shift($table);
                \Core\Db\Db::instance()->execute("DELETE FROM " . $table);
            }
            \Core\Db\Db::instance()->execute("SET FOREIGN_KEY_CHECKS=1");
        } else {
            throw new \Exception("clearTable is allowed only in testing env");
        }
    }
    public function traffic($count, $storage = NULL)
    {
        if (empty($storage)) {
            $storage = \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::FILE;
        }
        \Traffic\Service\SettingsService::instance()->updateValues(["draft_data_storage" => $storage, "cache_storage" => $storage]);
        \Traffic\Profiler\ProfilerService::instance()->resetState();
        $faker = new Faker\Faker();
        $campaign = $faker->campaign();
        $stream = $faker->stream($campaign);
        $this->output("Storage: " . $storage, true);
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        \Traffic\Profiler\ProfilerService::instance()->resetState();
        if ($storage == \Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::REDIS) {
            \Core\Db\Db::instance()->disable();
        }
        for ($i = 0; $i < $count; $i++) {
            $logger = new \Traffic\Logging\NullTrafficLogEntry();
            $payload = new \Traffic\Pipeline\Payload(["server_request" => \Traffic\Request\ServerRequest::build(), "response" => \Traffic\Response\Response::build(), "campaign" => $campaign, "raw_click" => new \Traffic\RawClick(["ip" => $faker->ip(), "ip_string" => $faker->ip(), "keyword" => $faker->keyword(), "source" => $faker->source(), "ua" => $faker->userAgent(), "referrer" => $faker->userAgent(), "se_referrer" => $faker->userAgent(), "datetime" => new \DateTime()])]);
            $pipeline = new \Traffic\Pipeline\Pipeline();
            $pipeline->firstLevelStages();
            $pipeline->start($payload, $logger);
            $this->output(".");
        }
        $this->output("", true);
        $str = \Traffic\Profiler\ProfilerService::instance()->step("Created " . $count . " visitors.", $count);
        \Traffic\Redis\Service\RedisStorageService::instance()->clean(true);
        $this->output($str, true);
        $this->output("CPU Usage: " . $this->_getCpuUsage(), true);
        return $str;
    }
    public function dataProcessor($count, $rounds)
    {
        \Traffic\Service\SettingsService::instance()->updateValues(["geodb" => \Component\GeoDb\Sypex\SypexCityLite::ID, "redis_server" => "127.0.0.1:6379/2"]);
        $faker = new Faker\Faker();
        $campaign = $faker->campaign();
        $profiler = new \Traffic\Profiler\ProfilerService();
        $profiler->resetState();
        $streams = [];
        for ($i = 0; $i < min(100, $count); $i++) {
            $streams[] = $faker->stream($campaign);
        }
        $this->output("", true);
        $startTime = microtime(true);
        $clicks = 0;
        for ($i = 0; $i < $rounds; $i++) {
            foreach (\Component\DelayedCommands\Repository\DelayedCommandsStorageRepository::instance()->getStorages() as $storage) {
                $storage->clean();
                \Traffic\CommandQueue\Service\DelayedCommandService::instance()->setStorage($storage);
                $this->output("Preparing environment...");
                \Traffic\Profiler\ProfilerService::instance()->resetState();
                for ($i = 0; $i < $count; $i++) {
                    $rawClick = $faker->rawClick();
                    $clicks++;
                    $stream = $streams[rand(0, count($streams) - 1)];
                    $rawClick->set("stream_id", $stream->getId());
                    $rawClick->set("campaign_id", $campaign->getId());
                    \Component\Clicks\DelayedCommand\AddClickCommand::saveClick($rawClick);
                }
                $this->output("Done", true);
                $this->output("Start command processor...");
                $case = new \Component\DelayedCommands\Processor\ProcessCommandQueue();
                $case->process(\Traffic\CommandQueue\Service\DelayedCommandService::instance());
                $this->output("Done", true);
                $str = $profiler->step("Processed " . $count . " commands.", $count);
                $this->output($str, true);
                $this->output("", true);
            }
        }
        $this->output("Total " . $clicks . " clicks", true);
        $this->output("Throughput " . ceil($clicks / (microtime(true) - $startTime) * 60) . " rpm", true);
        $this->output("CPU Usage: " . $this->_getCpuUsage(), true);
    }
    private function _getCpuUsage()
    {
        if (function_exists("sys_getloadavg")) {
            $execLoads = sys_getloadavg();
            $execCores = trim(shell_exec("grep '^processor' /proc/cpuinfo 2>&1 | wc -l  "));
            if (strstr($execCores, "No such file")) {
                $execCores = 1;
            }
            return round($execLoads[1] / ($execCores + 1) * 100, 0) . "%";
        }
        return "unknown";
    }
    public function output($str, $newLine = false)
    {
        if ($newLine) {
            $this->_output->writeln($str);
        } else {
            $this->_output->write($str);
        }
    }
}

?>