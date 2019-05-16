<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Log;

use Michael\Jobs\Utils;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ConsoleLog extends BaseLog
{
    protected $catalog = 'common';
    protected $loggerDrv = [];
    protected $output = STDOUT;

    public function __construct()
    {
        $config = Utils::app()->get('console_config')->getConfig();
        $this->output = Utils::arrayGet($config, 'log_path', $this->output);
        $this->loggerDrv = new Logger($this->catalog);
        $this->loggerDrv->pushHandler(new StreamHandler($this->output, Logger::DEBUG));
    }

    public function emergency($message, array $context = array())
    {
        return $this->loggerDrv->emergency($message, $context);
    }

    public function critical($message, array $context = array())
    {
        return $this->loggerDrv->critical($message, $context);
    }

    public function error($message, array $context = array())
    {
        return $this->loggerDrv->error($message, $context);
    }

    public function warning($message, array $context = array())
    {
        return $this->loggerDrv->warning($message, $context);
    }

    public function notice($message, array $context = array())
    {
        return $this->loggerDrv->notice($message, $context);
    }

    public function info($message, array $context = array())
    {
        return $this->loggerDrv->info($message, $context);
    }

    public function debug($message, array $context = array())
    {
        return $this->loggerDrv->debug($message, $context);
    }
}