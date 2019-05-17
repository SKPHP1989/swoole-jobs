<?php

namespace Michael\Jobs\Action;


use Michael\Jobs\ErrCode;
use Michael\Jobs\Interfaces\Action;
use Michael\Jobs\Interfaces\Task;
use Michael\Jobs\Utils;

class LaravelAction implements Action
{
    /**
     * @var \Illuminate\Foundation\Application
     */
    protected static $application = null;

    protected static $autloadPath = '';
    protected static $appPath = '';

    public function setAutoloadPath($path = '')
    {
        static::$autloadPath = $path;
    }

    public function setAppPath($path = '')
    {
        static::$appPath = $path;
    }

    /**
     * 启动
     * @param Task $task
     */
    public function start($task)
    {
        $application = self::getApplication();
        $closure = function ($task, $application) {
            $argv = [
                'artisan',
                $task->getHandleClass() . ':' . $task->getHandleMethod(),
                json_encode($task->getHandleParams())
            ];
            $application->handle(
                new \Symfony\Component\Console\Input\ArgvInput($argv),
                new \Symfony\Component\Console\Output\ConsoleOutput()
            );
        };
        Utils::runMethodExceptionHandle($closure, [$task, $closure]);
        unset($application, $JobObject);
    }

    /**
     * 获取应用实例
     * @return \Illuminate\Foundation\Application
     */
    private static function getApplication()
    {
        if (self::$application === null) {
            require static::$autloadPath;
            $app = require_once static::$appPath;
            self::$application = $app->make(\Illuminate\Contracts\Console\Kernel::class);
        }
        return self::$application;
    }
}
