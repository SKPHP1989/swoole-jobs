<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Output;


use Michael\Jobs\Interfaces\Output;

class BaseOutput implements Output
{
    public function error(string $msg)
    {
        echo $msg . PHP_EOL;
    }

    public function info(string $msg)
    {
        echo $msg . PHP_EOL;
    }

    public function warn(string $msg)
    {
        echo $msg . PHP_EOL;
    }
}