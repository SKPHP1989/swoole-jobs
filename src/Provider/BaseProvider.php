<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) kcloze <pei.greet@qq.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Michael\Jobs\Provider;


use Michael\Jobs\Config\BaseConfig;
use Michael\Jobs\Di;
use Michael\Jobs\Interfaces\Provider;
use Michael\Jobs\Utils;

class BaseProvider implements Provider
{
    /**
     * @var Di
     */
    static protected $container = null;
    static protected $registerSingletonMap;
    static protected $registerMap;

    /**
     *
     */
    public function setContainer(Di $container)
    {
        if (!static::$container) {
            static::$container = &$container;
        }
    }

    /**
     * @param $config
     */
    protected function registerCoreConfig($config)
    {
        $configClourse = function (string $item) use ($config) {
            $configObj = new BaseConfig();
            $itemConfig = Utils::arrayGet($config, $item, []);
            $configObj->setConfig($itemConfig);
            return $configObj;
        };
        static::$container->setShared('process_config', call_user_func($configClourse, 'process'));
        static::$container->setShared('console_config', call_user_func($configClourse, 'console'));
        static::$container->setShared('topic_config', call_user_func($configClourse, 'topics'));
        static::$container->setShared('queue_config', call_user_func($configClourse, 'queue'));
        static::$container->setShared('action_config', call_user_func($configClourse, 'action'));
    }

    /**
     * 注册变量
     * @param $config
     */
    public function registerCore($config)
    {
        $this->registerCoreConfig($config);
        foreach (static::$registerMap as $name => $class) {
            static::$container->set($name, $class);
        }
        foreach (static::$registerSingletonMap as $name => $class) {
            static::$container->setShared($name, $class);
        }
    }

    public function register($name, $class)
    {
        static::$container->set($name, $class);
    }

    public function registerSingleton($name, $class)
    {
        static::$container->setShared($name, $class);
    }

    public function get($name, $params = [])
    {
        return static::$container->get($name, $params);
    }
}
