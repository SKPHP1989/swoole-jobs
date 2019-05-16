<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:46
 */

namespace Michael\Jobs\Task;

use Michael\Jobs\ErrCode;
use Michael\Jobs\Interfaces\Task;
use Michael\Jobs\Utils;

class BaseTask implements Task
{
    public $uuid = '';
    public $topic = '';
    public $taskClass = '';
    public $taskMethod = '';
    public $taskParams = [];
    public $taskExtras = [];

    public function batchSet(array $config)
    {
        $this->topic = Utils::arrayGet($config, 'topic');
        $this->uuid = Utils::arrayGet($config, 'uuid', uniqid($this->topic) . '.' . microtime(true));
        $this->taskClass = Utils::arrayGet($config, 'taskClass');
        $this->taskMethod = Utils::arrayGet($config, 'taskMethod');
        $this->taskParams = Utils::arrayGet($config, 'taskParams');
        $this->taskExtras = Utils::arrayGet($config, 'taskExtras');
        return $this;
    }

    public function setUuid(string $uuid = '')
    {
        $this->uuid = $uuid ? $uuid : uniqid($this->topic) . '.' . microtime(true);
    }

    public function setTopic(string $topic)
    {
        $this->topic = $topic;
    }

    public function setHandleClass(string $class)
    {
        $this->taskClass = $class;
    }

    public function setHandleMethod(string $method)
    {
        $this->taskMethod = $method;
    }

    public function setHandleParams($params)
    {
        $this->taskParams = $params;
    }

    public function setHandleExtras(array $extras = [])
    {
        $this->taskExtras = $extras;
    }

    public function getUuid()
    {
        return $this->uuid;
    }

    public function getTopic()
    {
        return $this->topic;
    }

    public function getHandleClass()
    {
        return $this->taskClass;
    }

    public function getHandleMethod()
    {
        return $this->taskMethod;
    }

    public function getHandleParams()
    {
        return $this->taskParams;
    }

    public function getHandleExtras()
    {
        return $this->taskExtras;
    }

    /**
     * 写自己到队列
     */
    public function writeSelfToQueue()
    {
        $topicAllConfig = Utils::getProvider()->get('topic_config')->getConfig();
        $queueConfig = Utils::getProvider()->get('queue_config')->getConfig();
        $topicConfig = Utils::arrayGet($topicAllConfig, $this->topic);
        $queueDriverName = Utils::arrayGet($topicConfig, 'drive', 'redis_queue');
        $closure = function () use ($queueConfig, $queueDriverName) {
            $queueDriver = Utils::getProvider()->get($queueDriverName);
            if (empty($queueDriver)) {
                throw new \UnexpectedValueException($queueDriverName . ' queue driver is not exist!', ErrCode::CLASS_NOT_EXIST);
            }
            $driver = $queueDriver->getConnection((array)Utils::arrayGet($queueConfig, $queueDriverName));
            if (empty($driver)) {
                throw new \UnexpectedValueException($queueDriverName . ' queue driver config is not connect!', ErrCode::REMOTE_CONNECT_ERR);
            }
            return $driver;
        };
        $driver = Utils::runMethodExceptionHandle($closure);
        if (!$driver) {
            Utils::getProvider()->get("output")->error('Task :' . json_encode($this) . ' write fail!');
            return false;
        }
        return $driver->push($this->topic, $this);

    }
}