<?php

use \Michael\Jobs\Utils;

define('MJOBS_ROOT_PATH', __DIR__);
require MJOBS_ROOT_PATH . '/vendor/autoload.php';
date_default_timezone_set('Asia/Shanghai');
//加载配置
$config = require_once MJOBS_ROOT_PATH . '/config.php';
Utils::setConfig($config);
switch (php_sapi_name()){
    case 'cli':
        Utils::registerCliProvider();
        break;
    default:
        Utils::registerCgiProvider();
}
$provider = Utils::getProvider();
$provider->setContainer(Utils::app());
$provider->registerCore(Utils::getConfig());
/**
 * @var Michael\Jobs\Interfaces\Task $task
 */
$task = $provider->get('task');
$topic = 'MyJob';
$task->setTopic($topic);
$task->setUuid();
$task->setHandleClass('asd');
$task->setHandleMethod('asd');
$task->setHandleParams([]);
$task->writeSelfToQueue();