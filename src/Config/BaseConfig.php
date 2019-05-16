<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:46
 */

namespace Michael\Jobs\Config;
use Michael\Jobs\Interfaces\Config;

class BaseConfig implements Config
{
    private $_config = [];

    public function setConfig($config)
    {
        $this->_config = $config;
    }

    public function getConfig()
    {
        return $this->_config;
    }
}