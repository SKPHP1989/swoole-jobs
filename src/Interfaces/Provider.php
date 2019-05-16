<?php

/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:26
 */

namespace Michael\Jobs\Interfaces;


use Michael\Jobs\Di;

interface Provider
{
    public function setContainer(Di $container);

    public function registerCore($config);

    public function register($name, $class);

    public function registerSingleton($name, $class);

    public function get($name, $params = []);
}