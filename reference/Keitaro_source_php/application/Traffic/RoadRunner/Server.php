<?php
/*
 * @ https://EasyToYou.eu - IonCube v11 Decoder Online
 * @ PHP 7.2
 * @ Decoder version: 1.0.4
 * @ Release: 01/09/2021
 */

namespace Traffic\RoadRunner;

class Server
{
    private $_psrClient = NULL;
    private $_dumper = NULL;
    const SERVER_HOST = "127.0.0.1";
    const SERVER_PC_PORT = 6001;
    const RPC_COMMAND_RESET = "http.Reset";
    const MIN_PHP_VERSION = "7.1";
    public function __construct()
    {
        self::checkDependencies();
        $this->_setupPhp();
        $this->_psrClient = $this->_buildPsr7Client();
        $this->_dumper = $this->_buildDumper();
    }
    public static function checkDependencies($phpVersion = NULL)
    {
        if (empty($phpVersion)) {
            $phpVersion = phpversion();
        }
        if (version_compare($phpVersion, MIN_PHP_VERSION) < 0) {
            throw new \Exception("[RR] PHP version must be 7.1 or higher");
        }
        if (!extension_loaded("sockets")) {
            throw new \Exception("[RR] PHP extension 'sockets' is required!");
        }
    }
    public static function restart()
    {
        while (\Core\Application\Application::instance()->isRoadRunnerRunning() || \Component\System\Service\StatusService::instance()->isEngineRoadRunner()) {
            try {
                self::checkDependencies();
            } catch (\Exception $e) {
                \Traffic\Logging\Service\LoggerService::instance()->warning("The tracker is incompatible with RoadRunner" . $e->getMessage());
                try {
                    $rpc = new \Spiral\Goridge\RPC(new \Spiral\Goridge\SocketRelay(SERVER_HOST, SERVER_PC_PORT));
                    return $rpc->call(RPC_COMMAND_RESET, true);
                } catch (\Spiral\Goridge\Exceptions\RelayException $e) {
                    \Traffic\Logging\Service\LoggerService::instance()->warning("RoadRunner RPC message: " . $e->getMessage());
                }
            }
        }
    }
    public function start(\Core\Router\TrafficRouter $router)
    {
        \Core\Application\Bootstrap::initApplication();
        while ($psr7 = $this->_psrClient->acceptRequest()) {
            try {
                try {
                    \Traffic\Request\ServerRequestFactory::clearSuperGlobals();
                    $request = \Traffic\Request\ServerRequestFactory::fromPsr7Request($psr7);
                    $routerResult = $router->match($request);
                    $response = \Core\Kernel\Kernel::run($routerResult->serverRequest(), $routerResult->context());
                    $this->_logResponse($response);
                    $this->_psrClient->respond($response);
                } catch (\Throwable $e) {
                    $this->_processException($e);
                    \Traffic\Logging\Service\LoggerService::instance()->flush();
                    $this->_clearState();
                }
            } catch (\InvalidArgumentException $e) {
                $this->_processException($e);
                \Traffic\Logging\Service\LoggerService::instance()->flush();
                $this->start($router);
            }
        }
    }
    private function _clearState()
    {
        \Traffic\Repository\ParameterRepository::instance()->clearCache();
    }
    private function _processException(\Throwable $exception)
    {
        \Traffic\Logging\Service\LoggerService::instance()->error((int) $exception);
        if (\Core\Application\Application::instance()->isDebug()) {
            $errorMessage = (int) $exception;
        } else {
            $errorMessage = json_encode(["error" => "Internal error (RR). Please open the system log."]);
        }
        $this->_dumper->dump((int) $exception, \Spiral\Debug\Dumper::ERROR_LOG);
        $this->_psrClient->getWorker()->error($errorMessage);
        try {
            \Core\Db\Db::instance()->disconnect();
        } catch (\Exception $e) {
            \Traffic\Logging\Service\LoggerService::instance()->warning("[Server] " . $e->getMessage());
        }
    }
    private function _logResponse(\Traffic\Response\Response $response)
    {
        if (\Traffic\Logging\Service\LoggerService::instance()->getLevel() !== \Traffic\Logging\Service\LoggerService::DEBUG) {
            return NULL;
        }
        $this->_dumper->dump($response->serialize(), \Spiral\Debug\Dumper::ERROR_LOG);
    }
    private function _setupPhp()
    {
        ini_set("display_errors", "stderr");
    }
    private function _buildPsr7Client()
    {
        $worker = new \Spiral\RoadRunner\Worker(new \Spiral\Goridge\StreamRelay(STDIN, STDOUT));
        $factory = new \Laminas\Diactoros\ServerRequestFactory();
        return new \Spiral\RoadRunner\PSR7Client($worker, $factory);
    }
    private function _buildDumper()
    {
        $dumper = new \Spiral\Debug\Dumper();
        $dumper->setRenderer(\Spiral\Debug\Dumper::ERROR_LOG, new \Spiral\Debug\Renderer\ConsoleRenderer());
        return $dumper;
    }
}

?>