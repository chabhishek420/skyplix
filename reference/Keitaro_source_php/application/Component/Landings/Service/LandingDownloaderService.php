<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Component\Landings\Service;

class LandingDownloaderService
{
    private $_editorRepository = NULL;
    private $_packer = NULL;
    private $_localFileService = NULL;
    public function __construct()
    {
        $this->_editorRepository = new \Component\Editor\Repository\EditorRepository();
        $this->_packer = new \Component\Landings\LocalFile\Packer();
        $this->_localFileService = \Component\Landings\LocalFile\LocalFileService::instance();
    }
    public function getPackedFile($getPackedFile, $id, $modelType)
    {
        $localLanding = $this->_editorRepository->findModel($id, $modelType);
        $option = $localLanding->get("action_options");
        $this->_editorRepository->checkLocalType($option);
        $systemPath = $this->_localFileService->buildPath($localLanding->getFolder());
        return $this->_packer->pack($systemPath, $id, $modelType);
    }
    public function getHeadersDownload($filename)
    {
        return ["Content-Description" => "File Transfer", "Content-type" => "application/octet-stream", "Content-Disposition" => "attachment; filename=" . $filename, "Expires" => "0", "Cache-Control" => "must-revalidate", "Pragma" => "public"];
    }
}

?>