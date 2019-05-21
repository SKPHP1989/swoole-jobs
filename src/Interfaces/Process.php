<?php

/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:26
 */

namespace Michael\Jobs\Interfaces;
interface Process
{
    public function exec();

    public function registerSignal();

    public function saveMasterProcessInfo();

    public function registerTimer();

    public function createConsumeWorker($workerNo, $topic, $workerType);

    public function getMasterPid();

    public function getMasterStatus();

    public function exit();
}