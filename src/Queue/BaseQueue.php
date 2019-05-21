<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Michael\Jobs\Queue;


use Michael\Jobs\Interfaces\Queue;

abstract class BaseQueue implements Queue
{
    protected $topics = [];
    /**
     * @var Queue
     */
    protected $queueDriver = null;
    protected $queueDriverID = null;

    public function getConnection(array $config)
    {
    }

    public function getTopics()
    {
        //根据key大到小排序，并保持索引关系
        arsort($this->topics);

        return array_values($this->topics);
    }

    public function setTopics(array $topics)
    {
        $this->topics = $topics;
    }

    public function push($topic, $job): string
    {
    }

    public function pop($topic)
    {
    }

    /**
     * 清空队列，保留队列名.
     *
     * @param [type] $topic
     */
    public function purge($topic)
    {
    }

    /**
     * 删除队列.
     *
     * @param [type] $topic
     */
    public function delete($topic)
    {
    }

    public function len($topic): int
    {
    }

    public function close()
    {
    }

    public function isConnected()
    {
    }
}
