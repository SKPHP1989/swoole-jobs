<?php

namespace Michael\Jobs;

use Michael\Jobs\Config\BaseConfig;
use Michael\Jobs\Console\BaseConsole;
use Michael\Jobs\Input\BaseInput;
use Michael\Jobs\Job\BaseJob;
use Michael\Jobs\Output\BaseOutput;
use Michael\Jobs\Process\BaseProcess;

/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/10
 * Time: 12:07
 */
class Command
{
    protected $argv;

    /**
     * 注册类到容器
     * @param array $config
     */
    public function __construct(array $config)
    {
        Utils::app()->setShared('process_config', function () use ($config) {
            $configObj = new BaseConfig();
            $configObj->setConfig($config);
            return $configObj;
        });
        Utils::app()->setShared('console_config', function () use ($config) {
            $configObj = new BaseConfig();
            $configObj->setConfig($config);
            return $configObj;
        });
        Utils::app()->set('console', BaseConsole::class);
        Utils::app()->set('job', BaseJob::class);
        Utils::app()->set('process', BaseProcess::class);

        Utils::app()->setShared('output', BaseOutput::class);
        Utils::app()->setShared('input', BaseInput::class);
    }

    /**
     * 执行
     */
    public function run()
    {
        $consoleObj = Utils::app()->get('console');
        $consoleInputObj = Utils::app()->get('console_input');
        $act = array_shift($consoleInputObj->getArgument());
        switch ($act) {
            case Constants::ACT_START:
                $consoleObj->start();
                break;
            case Constants::ACT_RESTART:
                $consoleObj->restart();
                break;
            case Constants::ACT_STATUS:
                $consoleObj->status();
                break;
            case Constants::ACT_EXIT:
                $consoleObj->exit();
                break;
            case Constants::ACT_HELP:
            default:
                $consoleObj->help();
                break;
        }
    }
}