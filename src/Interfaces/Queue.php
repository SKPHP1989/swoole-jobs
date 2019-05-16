<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Queue
{
    /**
     * @param array $config
     * @return Queue
     */
    public function getConnection(array $config);

    /**
     * @return array a array of topics
     */
    public function getTopics();

    /**
     * @param array $topics
     */
    public function setTopics(array $topics);

    /**
     * 推送队列，返回jobid字符串.
     *
     * @param [type]    $topic
     * @param JobObject $job
     *
     * @return string
     */
    public function push($topic, $job): string;

    /**
     * 从队列拿消息.
     *
     * @param [type] $topic
     *
     * @return Task|array
     */
    public function pop($topic);

    /**
     * @param $topic
     *
     * @return int
     */
    public function len($topic): int;

    /**
     * close connection
     * @return mixed
     */
    public function close();

    /**
     *
     * @return mixed
     */
    public function isConnected();

}