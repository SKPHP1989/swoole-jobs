<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:31
 */

namespace Michael\Jobs;
final class Constants
{
    //
    const ACT_START = "start";
    const ACT_STOP = "stop";
    const ACT_STATUS = "status";
    const ACT_EXIT = "exit";
    const ACT_RESTART = "restart";
    const ACT_HELP = "help";
    //
    const PROC_WORKER_TYPE_STATIC = "static";
    const PROC_WORKER_TYPE_DYNAMIC = "dynamic";

    const PROC_WORKER_NAME_STATIC = "worker-static";
    const PROC_WORKER_NAME_DYNAMIC = "worker-dynamic";

    const RUN_STATUS_RUNING = 'running';
    const RUN_STATUS_WAIT = 'wait';
    const RUN_STATUS_STOP = 'stop';

}

final class ErrCode
{
    const CLASS_NOT_EXIST = 100001;
    const REMOTE_CONNECT_ERR = 100002;
    const RUN_ERR = 100003;
}