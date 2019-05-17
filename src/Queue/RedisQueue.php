<?php

namespace Michael\Jobs\Queue;

use Michael\Jobs\Interfaces\Output;
use Michael\Jobs\Interfaces\Serialize;
use Michael\Jobs\Utils;

/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/13
 * Time: 14:18
 */
class RedisQueue extends BaseQueue
{
    protected static $connections = [];
    /**
     * @var Output
     */
    protected $outputObj;
    /**
     * @var Serialize
     */
    protected $serializeObj;

    public function __construct()
    {
        $this->outputObj = Utils::app()->get('output');
        $this->serializeObj = Utils::app()->get('serialize');
    }

    /**
     * @param array $config
     * @return $this|bool|void
     */
    public function getConnection(array $config)
    {
        $configID = md5(json_encode($config));
        if (isset(static::$connections[$configID])) {
            $this->queueDriver = static::$connections[$configID];
            return $this;
        }
        $closure = function ($config, $configID) {
            $host = Utils::arrayGet($config, 'host', '127.0.0.1');
            $port = Utils::arrayGet($config, 'port', '6379');
            $password = Utils::arrayGet($config, 'password', '');
            static::$connections[$configID] = new \Redis();
            static::$connections[$configID]->connect($host, $port);
            if (!empty($password)) {
                static::$connections[$configID]->auth($password);
            }
            $this->queueDriver = static::$connections[$configID];
            return $this;
        };
        return Utils::runMethodExceptionHandle($closure, [$config, $configID]);
    }

    public function push($topic, $job): string
    {
        if (!$this->isConnected()) {
            return '';
        }
        $this->queueDriver->rPush($topic, $this->serializeObj->encode($job));
        return $job->uuid ?? '';
    }

    public function pop($topic)
    {
        if (!$this->isConnected()) {
            return;
        }
        $result = $this->queueDriver->lPop($topic);
        return !empty($result) ? $this->serializeObj->decode($result) : null;
    }

    public function len($topic): int
    {
        if (!$this->isConnected()) {
            return 0;
        }
        return (int)$this->queueDriver->lSize($topic) ?? 0;
    }

    public function purge($topic)
    {
        if (!$this->isConnected()) {
            return 0;
        }

        return (int)$this->queueDriver->ltrim($topic, 1, 0) ?? 0;
    }

    public function delete($topic)
    {
        if (!$this->isConnected()) {
            return 0;
        }

        return (int)$this->queueDriver->delete($topic) ?? 0;
    }

    public function close()
    {
        if (!$this->isConnected()) {
            return;
        }

        $this->queueDriver->close();
    }

    public function isConnected()
    {
        try {
            $this->queueDriver->ping();
        } catch (\Exception $e) {

            return false;
        }
        return true;
    }
}