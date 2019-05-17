<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:31
 */

namespace Michael\Jobs\Process;

use Michael\Jobs\Constants;
use Michael\Jobs\Interfaces\Config;
use Michael\Jobs\Interfaces\Output;
use Michael\Jobs\Interfaces\Process;
use Michael\Jobs\Utils;

class BaseProcess implements Process
{
    //类
    /**
     * @var Output
     */
    protected $outputObj;
    /**
     * @var Config
     */
    protected $processConfigObj;
    /**
     * @var Config
     */
    protected $topicConfigObj;
    protected $utilObj;
    //工作者信息
    protected $workerMap = [];
    protected $workerObjMap = [];
    protected $workerNum = 0;
    //配置
    protected $startTime = 0;
    //工作者最大执行时间
    protected $processNameSuffix = 'michael-job';
    protected $workerMaxExecTime = 1200;
    protected $queueBusyMax = 1000;
    protected $queueBusyCheckTimer = 20;
    protected $mpid = 0;
    protected $mstatus = '';
    protected $mpidFilepath;
    protected $minfoFilepath;
    protected $topics;
    protected $isDaemon = false;

    /**
     * BaseProcess constructor.
     */
    public function __construct()
    {
        //class
        $this->outputObj = Utils::app()->get('output');
        $this->processConfigObj = Utils::app()->get('process_config');
        $this->topicConfigObj = Utils::app()->get('topic_config');
        //config
        $config = $this->processConfigObj->getConfig();
        $this->topics = $this->topicConfigObj->getConfig();
        $this->processNameSuffix = $config['process_name_suffix'] ?? $this->processNameSuffix;
        $this->workerMaxExecTime = $config['worker_max_exec_time'] ?? $this->workerMaxExecTime;
        $this->queueBusyMax = $config['queue_busy_max'] ?? $this->queueBusyMax;
        $this->queueBusyCheckTimer = $config['queue_busy_check_timer'] ?? $this->queueBusyCheckTimer;
        $varPath = $config['var_path'] ?? '';
        if (empty($varPath)) {
            $this->outputObj->error('Config var path must be set!');
            exit();
        }
        Utils::mkdir($varPath);
        //path
        $this->mpidFilepath = realpath($varPath) . DIRECTORY_SEPARATOR . 'pid';
        $this->minfoFilepath = realpath($varPath) . DIRECTORY_SEPARATOR . 'pinfo';
        $this->startTime = time();
        $this->isDaemon = Utils::arrayGet($config, 'is_daemon', $this->isDaemon);
    }

    /**
     * 保持master进程信息
     */
    public function saveMasterProcessInfo()
    {
        $this->mpid = getmypid();
        $this->mstatus = Constants::RUN_STATUS_RUNING;
        //保存master信息
        $this->saveMasterPid($this->mpid);
        $this->saveMasterStatus($this->mstatus);
    }

    /**
     * @return bool
     */
    protected function killMpidIfExist()
    {
        $mpid = $this->getMasterPid();
        if (!$mpid) {
            Utils::getLog()->emergency('Master PID get fail');
            return true;
        }
        for ($i = 0; $i < 3; $i++) {
            if (@\Swoole\Process::kill($mpid, 0)) {
                $this->outputObj->error('Have master process is running,please end or restart first!');
                die(1);
            }
            sleep(1);
        }
    }

    /**
     * exec
     */
    public function exec()
    {
        //daemon process
        if ($this->isDaemon) {
            @\Swoole\Process::daemon();
        }
        $topics = $this->topics;
        //no topic quit
        if (!$topics) {
            return true;
        }
        foreach ($topics as $k => $topic) {
            $workerMinNum = Utils::arrayGet($topic, 'worker_min_num', 0);
            $name = Utils::arrayGet($topic, 'name', $k);
            if (empty($workerMinNum) || empty($name)) {
                $this->outputObj->error(sprintf("%s(%s) config is error", $name, $workerMinNum));
                continue;
            }
            for ($i = 0; $i < $workerMinNum; $i++) {
                $this->createConsumeWorker($i, $name, Constants::PROC_WORKER_TYPE_STATIC);
            }
        }
    }

    public function saveMasterPid($pid)
    {
        return @file_put_contents($this->mpidFilepath, $pid);
    }

    public function saveMasterStatus($status)
    {
        return @file_put_contents($this->minfoFilepath, $status);
    }

    public function getMasterPid()
    {
        return @file_get_contents($this->mpidFilepath);
    }

    public function getMasterStatus()
    {
        return @file_get_contents($this->minfoFilepath);
    }

    public function registerSignal()
    {
        //force x quit signal
        \Swoole\Process::signal(SIGTERM, function ($signo) {
            $this->outputObj->warn(sprintf('Force quit x signal(%u) arrived', $signo));
            $this->masterExit();
        });
        //force quit signal
        \Swoole\Process::signal(SIGKILL, function ($signo) {
            $this->outputObj->warn(sprintf('Force quit signal(%u) arrived', $signo));
            $this->masterExit();
        });
        //user quit signal
        \Swoole\Process::signal(SIGUSR1, function ($signo) {
            $this->outputObj->warn(sprintf('User quit signal(%u) arrived', $signo));
            $this->waitWorkers();
        });
        //child process quit signal
        \Swoole\Process::signal(SIGCHLD, function ($signo) {
            $this->outputObj->warn(sprintf('Child process quit signal(%u) arrived', $signo));
            do {
                //wait for child process quit
                $workerProcessQuitInfo = \Swoole\Process::wait(false);
                if (!$workerProcessQuitInfo) {
                    break;
                }
                $workerPid = $workerProcessQuitInfo['pid'];
                $workerObj = $this->workerObjMap[$workerPid];
                $topic = Utils::arrayGet($this->workerMap[$workerPid], 'topic', '');
                $this->status = $this->getMasterStatus();
                //master process status is running and this child process can be restarted
                $conditionOne = Constants::RUN_STATUS_RUNING == $this->mstatus;
                $conditionTwo = Constants::PROC_WORKER_TYPE_STATIC == $this->workerMap[$workerPid]['worker_type'];
                if (!$conditionOne || !$conditionTwo) {
                    continue;
                }
                try {
                    //start up new child process
                    for ($i = 0; $i < 3; ++$i) {
                        $newWorkerPid = $workerObj->start();
                        if ($newWorkerPid > 0) {
                            break;
                        }
                        sleep(1);
                    }
                    $this->workerMap[$newWorkerPid] = [
                        'worker_type' => Constants::PROC_WORKER_TYPE_STATIC,
                        'topic' => $topic
                    ];
                    $this->workerObjMap[$newWorkerPid] = $workerObj;
                    $this->workerNum++;
                } catch (\Exception $e) {
                    $this->outputObj->error($e->getMessage());
                    $this->outputObj->error($e->getTraceAsString());
                    continue;
                }
            } while (true);
        });
    }

    /**
     * register timer
     */
    public function registerTimer()
    {
        \Swoole\Timer::tick($this->queueBusyCheckTimer, function () {
            $this->outputObj->info(time() . ' is running');
        });
    }

    /**
     * exit
     */
    public function exit()
    {
        //modify master process status be stop
        $this->mstatus = Constants::RUN_STATUS_STOP;
        $this->saveMasterStatus($this->mstatus);
        $mpid = $this->getMasterPid();
        @\Swoole\Process::kill($mpid);
        sleep(1);
    }

    /**
     * master进程退出
     */
    protected function masterExit()
    {
        foreach ($this->workerMap as $pid => $worker) {
            \Swoole\Process::kill($pid);
            unset($this->workerMap[$pid]);
            unset($this->workerObjMap[$pid]);
            $this->workerNum--;
        }
        @unlink($this->minfoFilepath);
        @unlink($this->mpidFilepath);
        exit(0);
    }

    /**
     * 创建消费工作者
     * @param $workerNum
     * @param $topic
     * @param $workerType
     * @return bool
     */
    public function createConsumeWorker($workerNum, $topic, $workerType)
    {
        $reserveProcess = new \Swoole\Process(function ($worker) use ($workerNum, $topic, $workerType) {
            //check master process
            $this->checkMaster($worker);
            $beginTime = time();
            try {
                // set process name
                $workerProcessName = sprintf('%s:%s:%s:%s', $workerType, $topic, $workerNum, $this->processNameSuffix);
                if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
                    swoole_set_process_name($workerProcessName);
                }
                $jobObj = Utils::app()->get('job');
                $jobObj->setTopic($topic);
                do {
                    $jobObj->run($topic);
                    $this->status = $this->getMasterStatus();
                    // condition
                    $conditionOne = Constants::RUN_STATUS_RUNING == $this->mstatus;
                    $conditionTwo = Constants::PROC_WORKER_TYPE_STATIC == $workerType;
                    $conditionThree = time() < ($beginTime + $this->workerMaxExecTime);
                } while ($conditionOne && $conditionTwo && $conditionThree);
            } catch (\Exception $e) {
                $this->outputObj->error($e->getMessage());
            }
        });
        // start up worker process
        $pid = $reserveProcess->start();
        // record worker process info
        $this->workerObjMap[$pid] = $reserveProcess;
        $this->workerMap[$pid] = [
            'worker_type' => $workerType,
            'topic' => $topic
        ];
        $this->workerNum++;
        return true;
    }

    /**
     * 检查
     * @param $worker
     */
    protected function checkMaster(&$worker)
    {
        if (!@\Swoole\Process::kill($this->mpid, 0)) {
            $worker->exit();
        }
    }
}