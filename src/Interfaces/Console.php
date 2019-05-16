<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Console
{
    public function start();

    public function stop();

    public function help();

    public function restart();

    public function exit();

    public function status();
}