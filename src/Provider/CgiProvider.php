<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Michael\Jobs\Provider;


use Michael\Jobs\Console\BaseConsole;
use Michael\Jobs\Job\BaseJob;
use Michael\Jobs\Log\ConsoleLog;
use Michael\Jobs\Output\HtmlOutput;
use Michael\Jobs\Process\BaseProcess;
use Michael\Jobs\Queue\RedisQueue;
use Michael\Jobs\Serialize\JsonSerialize;
use Michael\Jobs\Task\BaseTask;

class CgiProvider extends BaseProvider
{
    /**
     * @var array
     */
    static protected $registerMap = [
        'console' => BaseConsole::class,
        'job' => BaseJob::class,
        'process' => BaseProcess::class,
        'task' => BaseTask::class,
        'serialize' => JsonSerialize::class,
        'redis_queue' => RedisQueue::class,
    ];
    /**
     * @var array
     */
    static protected $registerSingletonMap = [
        'log' => ConsoleLog::class,
        'output' => HtmlOutput::class,
    ];
}
