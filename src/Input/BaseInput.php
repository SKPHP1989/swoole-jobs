<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Input;

use Michael\Jobs\Interfaces\Input;

class BaseInput implements Input
{
    protected $argv;

    public function getArgument($name = '')
    {
        global $argv;
        $this->argv = $argv;
        array_shift($this->argv);
        return $this->argv;
    }

    public function getOption($option = '')
    {
        return null;
    }
}