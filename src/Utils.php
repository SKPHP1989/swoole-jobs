<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Michael\Jobs;

use Michael\Jobs\Input\HtmlInput;
use Michael\Jobs\Input\SymConsoleInput;
use Michael\Jobs\Interfaces\Log;
use Michael\Jobs\Interfaces\Provider;
use Michael\Jobs\Output\HtmlOutput;
use Michael\Jobs\Provider\ConsoleProvider;
use Michael\Jobs\Provider\CliProvider;
use Michael\Jobs\Provider\CgiProvider;

class Utils
{
    static $config;

    /**
     * 循环创建目录.
     *
     * @param mixed $path
     * @param mixed $recursive
     * @param mixed $mode
     */
    public static function mkdir($path, $mode = 0777, $recursive = true)
    {
        if (!is_dir($path)) {
            mkdir($path, $mode, $recursive);
        }
    }

    /**
     * @param array $array
     * @param string $key
     * @param null $default
     * @return mixed|null
     */
    public static function arrayGet(array $array, string $key, $default = null)
    {
        if (isset($array[$key])) {
            return $array[$key];
        } else {
            return $default;
        }
    }

    /**
     * 获取日志类
     * @return Log
     */
    public static function getLog()
    {
        return static::getProvider()->get('log');
    }

    public static function getConfig()
    {
        return static::$config;
    }

    public static function setConfig($config)
    {
        static::$config = $config;
    }

    /**
     * @return Di
     */
    public static function app()
    {
        static $_app = null;
        if (is_null($_app)) {
            $_app = new Di();
        }
        return $_app;
    }

    /**
     * @return Provider
     */
    public static function getProvider()
    {
        return static::app()->get('provider');
    }

    /**
     * 注册命令端提供者
     * @return true
     */
    public static function registerConsoleProvider()
    {
        return static::app()->setShared('provider', ConsoleProvider::class);
    }

    /**
     * 注册简单提供者
     */
    public static function registerCliProvider()
    {
        return static::app()->setShared('provider', CliProvider::class);
    }

    /**
     * 注册简单提供者
     */
    public static function registerCgiProvider()
    {
        return static::app()->setShared('provider', CgiProvider::class);
    }

    /**
     * @param \Closure $closure
     * @param array $params
     * @return bool|mixed
     */
    public static function runMethodExceptionHandle(\Closure $closure, array $params = [])
    {
        try {
            return call_user_func_array($closure, $params);
        } catch (\Exception $e) {
            Utils::getLog()->critical($e->getCode() . ':' . $e->getMessage());
            Utils::getLog()->critical($e->getTraceAsString());
        } catch (\Throwable $e) {
            Utils::getLog()->critical($e->getCode() . ':' . $e->getMessage());
            Utils::getLog()->critical($e->getTraceAsString());
        }
        return false;
    }

    /**
     * 运行
     * @param array $config
     * @throws \Exception
     */
    public static function run(array $config)
    {
        Utils::setConfig($config);
        //Console实例
        $appName = Utils::arrayGet($config, 'system', "Michael jobs app");
        $appVersion = Utils::arrayGet($config, 'version', "V1.0");
        $application = new \Symfony\Component\Console\Application($appName, $appVersion);
        // 注册命令
        $application->addCommands([
            new \Michael\Jobs\Command\JobStartCommand,
            new \Michael\Jobs\Command\JobHelpCommand,
            new \Michael\Jobs\Command\JobStopCommand,
        ]);
        $application->run();
    }
}
