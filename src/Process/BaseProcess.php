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
    protected $workerTopicMap = [];
    //配置
    protected $startTime = 0;
    //工作者最大执行时间
    protected $processNameSuffix = 'mjobs';
    protected $workerMaxExecTime = 1200;
    protected $queueBusyMax = 1000;
    protected $queueBusyCheckTimer = 20;
    protected $mpid = 0;
    protected $mstatus = '';
    protected $mpidFilepath;
    protected $minfoFilepath;
    protected $topics;
    protected $isDaemon = false;
    protected $errorHoldOnTime = 10;
    protected $enableDynamicWorker = true;

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
        $this->enableDynamicWorker = $config['enable_dynamic_worker'] ?? $this->enableDynamicWorker;
        $varPath = $config['var_path'] ?? '';
        if (empty($varPath)) {
            $this->outputObj->error('Config var path must be set!');
            exit(0);
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
        //daemon process
        if ($this->isDaemon) {
            @\Swoole\Process::daemon();
        }
        $this->setProcessName(sprintf('%s:%s', 'master', $this->processNameSuffix));
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

    protected function clearProcessLocalInfo()
    {
        @unlink($this->minfoFilepath);
        @unlink($this->mpidFilepath);
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
                $workerInfo = Utils::arrayGet($this->workerMap, $workerPid);
                if (empty($workerInfo)) {
                    Utils::getLog()->error($workerPid . ' worker process info is not exist');
                    continue;
                }
                $topic = Utils::arrayGet($workerInfo, 'topic', '');
                $workerType = Utils::arrayGet($workerInfo, 'worker_type', '');
                $workerObj = Utils::arrayGet($workerInfo, 'worker_obj', '');
                //动态进程不重启
                if ($workerType == Constants::PROC_WORKER_TYPE_DYNAMIC) {
                    unset($this->workerMap[$workerPid]);
                    $this->workerTopicMap[$topic][$workerType]--;
                    continue;
                }
                //master process status is running and this child process can be restarted
                if (Constants::RUN_STATUS_RUNING != $this->mstatus) {
                    unset($this->workerMap[$workerPid]);
                    $this->workerTopicMap[$topic][$workerType]--;
                    continue;
                }
                $closure = function ($workerObj, $topic) {
                    //start up new child process
                    for ($i = 0; $i < 3; ++$i) {
                        $newWorkerPid = $workerObj->start();
                        if ($newWorkerPid > 0) {
                            break;
                        }
                        sleep(1);
                    }
                    $workerType = Constants::PROC_WORKER_TYPE_STATIC;
                    $this->workerMap[$newWorkerPid] = [
                        'worker_type' => $workerType,
                        'worker_obj' => $workerObj,
                        'topic' => $topic
                    ];
                    if (isset($this->workerTopicMap[$topic][$workerType])) {
                        $this->workerTopicMap[$topic][$workerType]++;
                    } else {
                        $this->workerTopicMap[$topic][$workerType] = 1;
                    }
                };
                Utils::runMethodExceptionHandle($closure, [$workerObj, $topic]);
            } while (true);
        });
    }

    /**
     * register timer
     */
    public function registerTimer()
    {
        // Statistic
        \Swoole\Timer::tick(10 * 1000, function () {
            $info = sprintf('Master process %u running time is %.2f hours from %s', $this->mpid, (time() - $this->startTime) / 3600, date('Y-m-d H:i:s', $this->startTime));;
            $this->outputObj->info($info);
        });
        static $_dynamicWorkerNumMap = [];
        // boot dynamic customer process
        $this->enableDynamicWorker && \Swoole\Timer::tick($this->queueBusyCheckTimer, function () use (&$_dynamicWorkerNumMap) {
            // quit master process
            if ($this->getMasterStatus() != Constants::RUN_STATUS_RUNING) {
                $this->masterExit();
                return;
            }
            foreach ($this->topics as $k => $topicInfo) {
                $workerMinNum = Utils::arrayGet($topicInfo, 'worker_min_num', 0);
                $workerMaxNum = Utils::arrayGet($topicInfo, 'worker_max_num', 0);
                $topic = Utils::arrayGet($topicInfo, 'name', $k);
                if (empty($workerMinNum) || empty($topic) || empty($workerMaxNum)) {
                    $this->outputObj->warn(sprintf("Topic:%s,WorkerMinNum:%u,WorkerMaxNum:%u config is error", $topic, $workerMinNum, $workerMaxNum));
                    continue;
                }
                $closure = function ($topic) {
                    $jobObj = Utils::app()->get('job');
                    $jobObj->setTopic($topic);
                    $waitingAmount = $jobObj->getWaitingAmount();
                    $jobObj->clear();
                    return $waitingAmount;
                };
                $waitingAmount = Utils::runMethodExceptionHandle($closure, [$topic]);
                if ($waitingAmount === false) {
                    continue;
                }
                //超过挤压量
                if ($waitingAmount <= $this->queueBusyMax) {
                    $this->outputObj->warn($topic . ' current queue waiting amount is not busy');
                    continue;
                }
                $workerType = Constants::PROC_WORKER_TYPE_DYNAMIC;
                //
                $currentTopicTypeNum = $this->workerTopicMap[$topic][$workerType] ?? 0;
                if ($currentTopicTypeNum >= $workerMaxNum) {
                    $this->outputObj->warn('Current dynamic worker is max num');
                    continue;
                }
                if (isset($_dynamicWorkerNumMap[$topic])) {
                    $_dynamicWorkerNumMap[$topic]++;
                    $workerNum = $_dynamicWorkerNumMap[$topic];
                } else {
                    $workerNum = $_dynamicWorkerNumMap[$topic] = 0;
                }
                $this->createConsumeWorker($workerNum, $topic, $workerType);
            }
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
        if (empty($mpid)) {
            $this->outputObj->warn('Current host is not running job server');
            return;
        }
        @\Swoole\Process::kill($mpid);
        $this->outputObj->warn('Now is kill master process ' . $mpid);
        sleep(1);
        $this->clearProcessLocalInfo();
        $this->outputObj->warn('Current host running job server stoped success');
    }

    /**
     * master进程退出
     */
    protected function masterExit()
    {
        foreach ($this->workerMap as $pid => $worker) {
            @\Swoole\Process::kill($pid);
            unset($this->workerMap[$pid]);
            unset($this->workerObjMap[$pid]);
            $this->workerNum--;
        }
        $this->clearProcessLocalInfo();
        exit(0);
    }

    /**
     *
     * @param $workerNum
     * @param $topic
     * @param $workerType
     * @return bool
     */
    public function createConsumeWorker($workerNo, $topic, $workerType)
    {
        $reserveProcess = new \Swoole\Process(function ($worker) use ($workerNo, $topic, $workerType) {
            $info = sprintf("%s Worker process (%u) start to handle %s(%u)", $workerType, getmypid(), $topic, $workerNo);
            $this->outputObj->warn($info);
            Utils::getLog()->warning($info);
            //check master process exist
            $this->checkMaster($worker);
            $beginTime = time();
            $closure = function ($beginTime, $workerType, $topic, $workerNo) {
                $workerProcessName = sprintf('%s:%s:%s:%s', $workerType, $topic, $workerNo, $this->processNameSuffix);
                $this->setProcessName($workerProcessName);
                $jobObj = Utils::app()->get('job');
                $jobObj->setTopic($topic);
                do {
                    $jobObj->run($topic);
                    $this->mstatus = $this->getMasterStatus();
                    // condition
                    $conditionOne = Constants::RUN_STATUS_RUNING == $this->mstatus;
                    $conditionTwo = Constants::PROC_WORKER_TYPE_STATIC == $workerType;
                    $conditionThree = time() < ($beginTime + $this->workerMaxExecTime);
                } while ($conditionOne && $conditionTwo && $conditionThree);
                $jobObj->clear();
                return true;
            };
            $ret = Utils::runMethodExceptionHandle($closure, [$beginTime, $workerType, $topic, $workerNo]);
            //运行异常
            if ($ret == false) {
                sleep($this->errorHoldOnTime);
            }
        });
        // start up worker process
        $pid = $reserveProcess->start();
        // record worker process info
        $this->workerMap[$pid] = [
            'worker_type' => $workerType,
            'topic' => $topic,
            'worker_obj' => $reserveProcess
        ];
        if (isset($this->workerTopicMap[$topic][$workerType])) {
            $this->workerTopicMap[$topic][$workerType]++;
        } else {
            $this->workerTopicMap[$topic][$workerType] = 1;
        }
        return true;
    }

    /**
     * @param $workerProcessName
     */
    protected function setProcessName($workerProcessName)
    {
        if (function_exists('swoole_set_process_name') && PHP_OS != 'Darwin') {
            swoole_set_process_name($workerProcessName);
        }
    }

    /**
     * 检查主进程是否存活
     * @param $worker
     */
    protected function checkMaster(&$worker)
    {
        if (!@\Swoole\Process::kill($this->mpid, 0)) {
            $worker->exit();
        }
    }
}