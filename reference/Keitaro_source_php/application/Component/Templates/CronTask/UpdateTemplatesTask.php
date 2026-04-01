<?php

namespace Component\Templates\CronTask;

class UpdateTemplatesTask implements \Component\Cron\CronTaskInterface
{
    public $channel = \Cron\CronTaskRunner\CronChannel::OTHER;
    const INTERVAL_HOURS = 12;
    public function run()
    {
        try {
            $downloader = new \Component\Templates\TemplateDownloader\TemplateDownloader(\Component\Templates\Info\Info::TEMPLATES);
            $downloader->download();
        } catch (\Component\Templates\Error\DownloaderError $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
        }
    }
    public function isReady(\DateTime $executedAt)
    {
        try {
            $comparableDate = new \DateTimeImmutable("-" . INTERVAL_HOURS . " minutes");
            return empty($executedAt) || $executedAt < $comparableDate;
        } catch (\Exception $e) {
            return false;
        }
    }
}

?>