<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\LocalFile;

class Packer
{
    const ARCHIVE_FOLDER = "/archive";
    const ARCHIVE_NAME = "_archive.zip";
    public function pack($systemPath, $landing_id, $type)
    {
        $zip = new \ZipArchive();
        $directory = \Traffic\Repository\CachedSettingsRepository::instance()->get(\Traffic\Model\Setting::LP_DIR, "lander");
        $archivePath = join("/", [ROOT, $directory]) . ARCHIVE_FOLDER . "/";
        if (!is_dir($archivePath)) {
            mkdir($archivePath, 493, true);
        }
        $fileName = $landing_id . "_" . $type . ARCHIVE_NAME;
        $filePath = $archivePath . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $zip->open($filePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($systemPath), \RecursiveIteratorIterator::LEAVES_ONLY);
        foreach ($files as $name => $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($systemPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        return ["path" => join("/", [ROOT, $directory]) . ARCHIVE_FOLDER . "/" . $fileName, "name" => $fileName];
    }
}

?>