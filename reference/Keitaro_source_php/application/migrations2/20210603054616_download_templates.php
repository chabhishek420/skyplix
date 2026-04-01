<?php

use Component\Templates\Error\DownloaderError;
use Component\Templates\Info\Info;
use Component\Templates\TemplateDownloader\TemplateDownloader;

class Migration_20210603054616_DownloadTemplates extends Migration
{
    const DESCRIPTION_RU = 'Загрузка файлов шаблонов';

    const DESCRIPTION_EN = 'Download new ts and network templates';

    /**
     * @throws DownloaderError
     */
    public static function up()
    {
        $downloader = new TemplateDownloader(Info::TEMPLATES);
        $downloader->download();
    }
}
