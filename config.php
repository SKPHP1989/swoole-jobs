<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

return $config = [
    'console' => [
        //项目/系统标识
        'system' => 'Michael-Jobs',
        'version' => 'V1.0',
        'log_path' => STDOUT,
    ],
    'process' => [
        //log目录
        'var_path' => __DIR__ . '/var/',
        'sleep' => 2,
        'queue_busy_max' => 1000,
        'worker_max_exec_time' => 100,
        'queue_busy_check_timer' => 1000 * 15,
        'process_name_suffix' => 'mjob',
        'is_daemon' => false,
        'single_job_exec_max_time' => 30,
        'single_job_exec_min_time' => 0.0001,
    ],
    'topics' => [
        'MyJob' => [
            'name' => 'MyJob',
            'worker_min_num' => 3,
            'worker_max_num' => 30,
            'driver' => 'redis_queue',
            'idle_sleep_time' => 3,
            'max_pop_num' => 1000,
        ],
        'MyJob2' => [
            'name' => 'MyJob2',
            'worker_min_num' => 3,
            'worker_max_num' => 30,
            'driver' => 'redis_queue',
            'idle_sleep_time' => 3,
            'max_pop_num' => 1000,
        ],
    ],
    'queue' => [
        'redis_queue' => [
            'host' => '127.0.0.1',
            'port' => 6379
        ],
        'rabbit_queue' => [
            'host' => '127.0.0.1',
            'port' => 6379
        ],

    ],
    'action' => [
        'class' => \Michael\Jobs\Action\BaseAction::class,
        'autoload_path' => '',
        'app_path' => '',
    ]
];
