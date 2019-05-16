<?php

/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:26
 */

namespace Michael\Jobs\Interfaces;
interface Output
{
    public function info(string $msg);

    public function warn(string $msg);

    public function error(string $msg);
}