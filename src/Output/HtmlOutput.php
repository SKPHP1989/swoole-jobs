<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Output;


use Michael\Jobs\Interfaces\Output;

class HtmlOutput implements Output
{
    public function error(string $msg)
    {
        echo sprintf('<red>%s</red>', $msg);
    }

    public function info(string $msg)
    {
        echo sprintf('<green>%s</green>', $msg);
    }

    public function warn(string $msg)
    {
        echo sprintf('<yellow>%s</yellow>', $msg);
    }
}