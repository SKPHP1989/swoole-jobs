<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:46
 */

namespace Michael\Jobs\Job;

use Michael\Jobs\Constants;
use Michael\Jobs\ErrCode;
use Michael\Jobs\Interfaces\Action;
use Michael\Jobs\Interfaces\Config;
use Michael\Jobs\Interfaces\Output;
use Michael\Jobs\Interfaces\Job;
use Michael\Jobs\Interfaces\Process;
use Michael\Jobs\Interfaces\Queue;
use Michael\Jobs\Interfaces\Serialize;
use Michael\Jobs\Utils;

class BaseJob implements Job
{
    /**
     * @var Serialize
     */
    protected $serializeObj;
    /**
     * @var Output
     */
    protected $outputObj;
    /**
     * @var Process
     */
    protected $processObj;
    /**
     * @var Config
     */
    protected $queueConfig;
    protected $topicConfig;
    /**
     * @var Queue
     */
    protected $queueDriver;
    protected $idleSleepSec = 5;
    protected $maxPopNum = 1000;
    protected $currentPopNum = 0;
    protected $idlePopTimes = 0;
    protected $topic = '';

    /**
     * BaseJob constructor.
     * @param $topic
     */
    public function __construct()
    {
        $this->serializeObj = Utils::app()->get('serialize');
        $this->outputObj = Utils::app()->get('output');
        $this->queueConfig = Utils::app()->get('queue_config')->getConfig();
        $this->processObj = Utils::app()->get('process');
    }

    /**
     * set topic
     * @param $topic
     */
    public function setTopic($topic)
    {
        $this->topic = $topic;
        $topicAllConfig = Utils::app()->get('topic_config')->getConfig();
        $topicConfig = Utils::arrayGet($topicAllConfig, $this->topic);
        $this->idleSleepSec = Utils::arrayGet($topicConfig, 'idle_sleep_time', 2);
        $this->maxPopNum = Utils::arrayGet($topicConfig, 'max_pop_num', 1000);
        $queueDriverName = Utils::arrayGet($topicConfig, 'drive', 'redis_queue');
        $this->queueDriver = Utils::app()->get($queueDriverName);
        if (empty($this->queueDriver)) {
            throw new \UnexpectedValueException($queueDriverName . ' queue driver is not exist!', ErrCode::CLASS_NOT_EXIST);
        }
        $conn = $this->queueDriver->getConnection(Utils::arrayGet($this->queueConfig, $queueDriverName));
        if (empty($conn)) {
            throw new \UnexpectedValueException($queueDriverName . ' queue driver is not connect!', ErrCode::REMOTE_CONNECT_ERR);
        }
    }

    /**
     * @return bool
     */
    public function run()
    {
        $len = $this->queueDriver->len($this->topic);
        if ($len <= 0) {
            sleep($this->idleSleepSec);
            return true;
        }
        $actionInstance = $this->instanceAction();
        do {
            $masterStatus = $this->processObj->getMasterStatus();
            if ($masterStatus != Constants::RUN_STATUS_RUNING) {
                break;
            }
            $task = $this->queueDriver->pop($this->topic);
            if (empty($task)) {
                $this->idlePopTimes++;
                if ($this->idlePopTimes >= 10) {
                    break;
                }
                usleep(1000);
                continue;
            }
            $type = gettype($task);
            if ($type == 'object') {
                $realTask = $task;
            } elseif ($type == 'array') {
                $realTask = Utils::app()->get('task')->batchSet($task);
            } else {
                $this->outputObj->error($this->serializeObj->encode($task) . ' message body cannot be handle!');
                Utils::getLog()->error($this->serializeObj->encode($task) . ' message body cannot be handle!');
                continue;
            }
            $this->outputObj->info('Task ' . json_encode($realTask) . ' is handling now');
            $closure = function () use ($actionInstance, $realTask) {
                $actionInstance->start($realTask);
            };
            Utils::runMethodExceptionHandle($closure);
            $this->currentPopNum++;
        } while ($this->maxPopNum >= $this->currentPopNum);
    }

    /**
     *
     */
    public function getWaitingAmount()
    {
        $len = $this->queueDriver->len($this->topic);
        $this->queueDriver->close();
        return $len;
    }

    /**
     * @return Action
     */
    protected function instanceAction()
    {
        $actionConfig = Utils::app()->get('action_config')->getConfig();
        $actionClassName = Utils::arrayGet($actionConfig, 'class', \Michael\Jobs\Action\BaseAction::class);
        /**
         * @var Action $actionInstance
         */
        $actionInstance = new $actionClassName;
        $actionInstance->setAutoloadPath(Utils::arrayGet($actionConfig, 'autoload_path'));
        $actionInstance->setAppPath(Utils::arrayGet($actionConfig, 'app_path'));
        return $actionInstance;
    }
}