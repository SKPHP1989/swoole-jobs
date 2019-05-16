<?php

namespace Michael\Jobs\Action;


use Michael\Jobs\Interfaces\Action;
use Michael\Jobs\Utils;

class BaseAction implements Action
{

    public function setAutoloadPath($path = '')
    {

    }

    public function setAppPath($path = '')
    {

    }

    public function start($task)
    {
        Utils::getLog()->info(json_encode($task) . ' is handling');
        $closure = function () use ($task) {
            $jobClass = $task->getHandleClass();
            $jobMethod = $task->getHandleMethod();
            $jobParams = $task->getHandleParams();
            $obj = new $jobClass();
            if (is_object($obj) && method_exists($obj, $jobMethod)) {
                call_user_func_array([$obj, $jobMethod], $jobParams);
            }
        };
        Utils::getLog()->info(json_encode($task) . ' is handled');
        Utils::runMethodExceptionHandle($closure);
    }
}