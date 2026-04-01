<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class PreviewImageService extends \Traffic\Service\AbstractService
{
    const SCREENSHOT_SERVICE = "http://screenshot.keitaro24.ru/";
    const PREVIEW_FILE = "_preview.png";
    const SECRET = "WL62vCM";
    const DEV_ENV_URI = "https://imgflip.com/i/2oh1lf";
    const TIMEOUT = 10;
    public function createPreview($domain, $systemPath)
    {
        $localPath = LocalFileService::instance()->absoluteToLocalPath($systemPath);
        $imageSystemPath = PreviewImageService::instance()->previewImagePath($systemPath);
        $lpUrl = LocalFileService::instance()->buildUrl($domain, $localPath);
        if (\Core\Application\Application::instance()->isDevelopment()) {
            $lpUrl = DEV_ENV_URI;
        }
        $lpUrl = str_replace(["http://", "https://"], "", $lpUrl);
        $lpUrl = urlencode($lpUrl);
        $requestUrl = SCREENSHOT_SERVICE . $lpUrl;
        if (file_exists($imageSystemPath)) {
            \Core\FileSystem\Service\FileSystemService::instance()->remove($imageSystemPath);
        }
        try {
            $headers = ["x-secret" => SECRET, "x-license-key" => \Core\Application\LicenseService::instance()->getKey()];
            $options = [\GuzzleHttp\RequestOptions::CONNECT_TIMEOUT => TIMEOUT, \GuzzleHttp\RequestOptions::READ_TIMEOUT => TIMEOUT];
            \Traffic\Http\Service\HttpService::instance()->download($requestUrl, $imageSystemPath, $headers, $options);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
        }
    }
    public function previewImagePath($path)
    {
        return $path . "/" . PREVIEW_FILE;
    }
}

?>