<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\Actions\Predefined;

class LocalFile extends \Traffic\Actions\AbstractAction
{
    const FOLDER = "folder";
    const NO_INDEX_FILE = "Error: LP must contain index file. Please read the system log file.";
    public function getType()
    {
        return TYPE_HIDDEN;
    }
    public function getField()
    {
        return UPLOAD;
    }
    public function _execute()
    {
        $pageContext = new \Core\Sandbox\SandboxContext(["raw_click" => $this->getRawClick(), "server_request" => $this->getServerRequest(), "stream" => $this->getStream(), "campaign" => $this->getCampaign()]);
        $pageInfo = new \Component\Landings\LocalFile\PageInfo($this->getServerRequest()->getUri(), $this->getActionOptions(), \Component\Landings\LocalFile\LocalFileService::instance()->isPhpAllowed());
        try {
            $response = \Component\Landings\LocalFile\PageWrapper::wrap($pageInfo, $pageContext);
            $this->setContent($response->getBody());
            $this->setStatus($response->getStatusCode());
            foreach ($response->getHeaders() as $name => $values) {
                $this->header($name, $values);
            }
        } catch (\Component\Landings\LocalFile\Validator\IncompatibleLocalFile $e) {
            \Traffic\Logging\Service\LoggerService::instance()->error($e->getMessage());
            if (\Core\Application\Application::instance()->isDebug() || \Core\Application\Application::instance()->isDevelopment()) {
                $this->setContent($e->getMessage());
            } else {
                $this->setContent(NO_INDEX_FILE);
            }
            $this->setDestinationInfo("LP");
        }
    }
}

?>