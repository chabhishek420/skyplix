<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Benchmark;

class Seeder
{
    private $_cachedCampaigns = NULL;
    private $_output = NULL;
    private $_statuses = NULL;
    public function __construct(\Symfony\Component\Console\Output\OutputInterface $output)
    {
        $this->_output = $output;
        \Traffic\Profiler\ProfilerService::instance()->enableXhprof();
    }
    public function createClicks($count, $interval, $collect = false)
    {
        $faker = new Faker\Faker();
        $result = [];
        $logger = new \Traffic\Logging\NullTrafficLogEntry();
        \Traffic\CachedData\Repository\CachedDataRepository::instance()->warmup();
        \Traffic\Profiler\ProfilerService::instance()->resetState();
        for ($i = 0; $i < $count; $i++) {
            $date = $this->_getRandomDate($interval);
            $campaign = $this->_getRandomCampaign();
            $rawClick = new \Traffic\RawClick($faker->getRawClickParams($date));
            $payload = new \Traffic\Pipeline\Payload(["raw_click" => $rawClick, "campaign" => $campaign, "server_request" => \Traffic\Request\ServerRequest::build(), "response" => \Traffic\Response\Response::build()]);
            $pipeline = new \Traffic\Pipeline\Pipeline();
            $pipeline->firstLevelStages();
            $payload = $pipeline->start($payload, $logger);
            if (!empty($response) && !empty($response->getInfo()["landing_id"]) && !empty($response->getInfo()["offer_id"]) && rand(0, 1) == 1) {
                \Traffic\Command\DelayedCommand\UpdateClickCommand::saveLpClick($response->getInfo()["sub_id"], $response->getInfo()["offer_id"], [], $response->getInfo()["landing_id"]);
            }
            $this->_output->write(".");
            if ($collect) {
                $rawClicks = $payload->getRawClicksToStore();
                $result[] = $rawClicks[count($rawClicks) - 1];
            } else {
                unset($rawClick);
            }
        }
        \Core\Db\Db::instance()->enable();
        $msg = "Done. " . \Traffic\CommandQueue\Service\DelayedCommandService::instance()->queueSize() . " commands in command queue.";
        $this->_output->writeln(\Traffic\Profiler\ProfilerService::instance()->step($msg, $count));
        \Core\Db\Db::instance()->enable();
        return $result;
    }
    public function createConversions($count, $interval)
    {
        $clicks = $this->createClicks($count, $interval, true);
        if (!count($clicks)) {
            throw new \Exception("no clicks");
        }
        for ($i = 0; $i < $count; $i++) {
            $click = $clicks[rand(0, count($clicks) - 1)];
            if (!empty($click)) {
                if (\Traffic\Repository\CachedSettingsRepository::instance()->get("currency") == "USD") {
                    $revenue = $this->_rand(2, 4);
                    $cost = $this->_rand(0, 0);
                } else {
                    $revenue = $this->_rand(15, 50);
                    $cost = $this->_rand(1, 2);
                }
                $status = $this->_randomStatus();
                $postback = \Component\Postback\Postback::buildFromParams(["sub_id" => $click->get("sub_id"), "status" => $status, "original_status" => $status, "revenue" => $revenue, "cost" => $cost]);
                \Component\Postback\DelayedCommand\ProcessPostbackCommand::processPostback($postback);
            }
        }
    }
    private function _randomStatus()
    {
        return $this->_statuses[rand(0, count($this->_statuses) - 1)];
    }
    private function _rand($min, $max)
    {
        return mt_rand($min * 10, $max * 10) / 10;
    }
    private function _getActiveCampaigns()
    {
        return \Component\Campaigns\Repository\CampaignRepository::instance()->allActive();
    }
    private function _getRandomCampaign()
    {
        if (!isset($this->_cachedCampaigns)) {
            $this->_cachedCampaigns = $this->_getActiveCampaigns();
        }
        return $this->_cachedCampaigns[rand(0, count($this->_cachedCampaigns) - 1)];
    }
    private function _getRandomDate($interval = 0)
    {
        $date = new \DateTime("-" . rand(0, (int) $interval) . " minutes", new \DateTimeZone("UTC"));
        return $date;
    }
}

?>