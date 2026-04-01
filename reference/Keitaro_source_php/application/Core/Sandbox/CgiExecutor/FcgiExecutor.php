<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Core\Sandbox\CgiExecutor;

class FcgiExecutor implements CgiExecutorInterface
{
    private $_binaryPath = NULL;
    private $_socketPath = NULL;
    private $_searched = false;
    private $_binaryFromConfig = NULL;
    private $_socketFromConfig = NULL;
    private $_statusService = NULL;
    const DEFAULT_FCGI_PATH = "/usr/bin/cgi-fcgi";
    const FCGI_PATH_KEY = "fcgi_path";
    public function __construct($binaryFromConfig, $socketFromConfig)
    {
        $this->_binaryFromConfig = $binaryFromConfig;
        $this->_socketFromConfig = $socketFromConfig;
    }
    public function setStatusService(\Component\System\Service\StatusService $service)
    {
        $this->_statusService = $service;
    }
    public function getStatusService()
    {
        if (!empty($this->_statusService)) {
            return $this->_statusService;
        }
        return \Component\System\Service\StatusService::instance();
    }
    public function isAvailable()
    {
        if (!function_exists("proc_open")) {
            return false;
        }
        $this->_fillInfo();
        if (file_exists($this->_socketPath) && (!is_readable($this->_socketPath) || !is_writable($this->_socketPath))) {
            return false;
        }
        return !empty($this->_binaryPath);
    }
    public function command()
    {
        $this->_fillInfo();
        return [$this->_binaryPath, "-bind", "-connect", $this->_socketPath];
    }
    private function _fillInfo()
    {
        if (!$this->_searched) {
            $statusService = $this->getStatusService();
            if ($statusService->isFCGIInstalled() && $statusService->getFCGISocketPath()) {
                $this->_binaryPath = !empty($this->_binaryFromConfig) ? $this->_binaryFromConfig : DEFAULT_FCGI_PATH;
                $this->_socketPath = $statusService->getFCGISocketPath();
            } else {
                if (!empty($this->_binaryFromConfig) && !empty($this->_socketFromConfig)) {
                    $this->_binaryPath = $this->_binaryFromConfig;
                    $this->_socketPath = $this->_socketFromConfig;
                }
            }
            $this->_searched = true;
        }
    }
}

?>