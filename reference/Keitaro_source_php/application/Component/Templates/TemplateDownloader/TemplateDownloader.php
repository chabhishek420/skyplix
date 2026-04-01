<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Templates\TemplateDownloader;

class TemplateDownloader
{
    private $_templates = NULL;
    private $_httpService = NULL;
    private $_fsService = NULL;
    const DOWNLOAD_TIMEOUT = 30;
    const CHECK = "<?php";
    public function __construct($templates)
    {
        $this->_templates = $templates;
        $this->_httpService = \Traffic\Http\Service\HttpService::instance();
        $this->_fsService = \Core\FileSystem\Service\FileSystemService::instance();
    }
    public function download($download)
    {
        foreach ($this->_templates as $source => $destination) {
            try {
                $tmpFile = $destination . "_tmp";
                $this->_httpService->download($source, $tmpFile, [], ["timeout" => DOWNLOAD_TIMEOUT]);
                if (!strstr(file_get_contents($tmpFile), CHECK)) {
                    throw new \Component\Templates\Error\DownloaderError("Template file '" . $tmpFile . "' is invalid");
                }
                $this->_fsService->rename($tmpFile, $destination, true);
            } catch (\GuzzleHttp\Exception\GuzzleException $e) {
                throw new \Component\Templates\Error\DownloaderError($e->getMessage(), $e->getCode(), $e);
            }
        }
    }
}

?>