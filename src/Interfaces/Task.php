<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Task
{
    public function batchSet(array $config);

    public function setUuid(string $uuid = '');

    public function setTopic(string $topic);

    public function setHandleClass(string $class);

    public function setHandleMethod(string $method);

    public function setHandleParams($params);

    public function setHandleExtras(array $extras = []);

    public function getUuid();

    public function getTopic();

    public function getHandleClass();

    public function getHandleParams();

    public function getHandleMethod();

    public function getHandleExtras();

    public function writeSelfToQueue();
}