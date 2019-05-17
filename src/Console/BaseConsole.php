<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Console;


use Michael\Jobs\Interfaces\Console;
use Michael\Jobs\Interfaces\Log;
use Michael\Jobs\Interfaces\Output;
use Michael\Jobs\Interfaces\Process;
use Michael\Jobs\Utils;

class BaseConsole implements Console
{
    /**
     * @var Log
     */
    protected $logObj;
    /**
     * @var Output
     */
    protected $outputObj;
    /**
     * @var Process
     */
    protected $processObj;

    public function __construct()
    {
        $this->outputObj = Utils::app()->get('output');
        $this->processObj = Utils::app()->get('process');
        $this->logObj = Utils::app()->get('log');
        $this->logObj->info('hello world 1989!');
    }

    public function start()
    {
        $this->outputObj->info('Now is starting process,please wait for a moment');
        $this->processObj->saveMasterProcessInfo();
        $this->processObj->exec();
        $this->processObj->registerSignal();
        $this->processObj->registerTimer();
    }

    public function exit()
    {
        $this->outputObj->info('Now is exiting process,please wait for a moment');
        $this->processObj->exit();
        $this->outputObj->info('Now exited process');
    }

    public function restart()
    {
        $this->outputObj->info('Now is restarting process,please wait for a moment');
        $this->processObj->exit();
        sleep(3);
        $this->processObj->saveMasterProcessInfo();
        $this->processObj->exec();
        $this->processObj->registerSignal();
        $this->processObj->registerTimer();
    }

    public function stop()
    {
        $this->outputObj->info('Now is stoping process,please wait for a moment');
        $this->processObj->exit();
        $this->outputObj->info('Now stoped process');
    }

    public function status()
    {
        $this->outputObj->info('The status method is empty!');
    }

    public function help()
    {
        $msg = <<<'EOF'
NAME
      php swoole-jobs - manage swoole-jobs

SYNOPSIS
      php swoole-jobs command [options]
          Manage swoole-jobs daemons.

WORKFLOWS

      help [command]
      Show this help, or workflow help for command.

      restart
      Stop, then start swoole-jobs master and workers.

      start
      Start swoole-jobs master and workers.

      start http
      Start swoole http server for apis.

      stop
      Wait all running workers smooth exit, please check swoole-jobs status for a while.
      
      stop http
      Stop swoole http server for api.

      exit
      Kill all running workers and master PIDs.

      exit http
      Stop swoole http server for api.


EOF;
        $this->outputObj->info($msg);
    }
}