<?php

namespace Cron\Dispatcher;

class CronDispatcher implements \Core\Dispatcher\DispatcherInterface
{
    public function dispatch(\Traffic\Request\ServerRequest $request)
    {
        \Traffic\Profiler\ProfilerService::instance()->resetState();
        \Traffic\Profiler\ProfilerService::instance()->step("Start cron tasks");
        // $payload = \Core\Application\EssentialService::instance()->checkIfTokenUpdated();
        // \Core\Application\EssentialService::instance()->validate($payload);
        $runner = new \Cron\CronTaskRunner\CronTaskRunner();
        $runner->runTasks($request->getQueryParam("channel"));
        $response = \Traffic\Response\ResponseFactory::build(["status" => 200, "disable_cache" => true, "body" => \Traffic\Response\ResponseFactory::safeBody(\Traffic\Profiler\ProfilerService::instance()->step("CronTasks"))]);
        if (\Core\Application\Application::instance()->isCli()) {
            $response = $response->withBody(\Traffic\Response\ResponseFactory::safeBody(""));
        }
        return $response;
    }
}

?>