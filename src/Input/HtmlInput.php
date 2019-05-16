<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Input;

use Michael\Jobs\Interfaces\Input;

class HtmlInput implements Input
{
    public function getArgument($name = '')
    {
        return null;
    }

    public function getOption($option = '')
    {
        return null;
    }
}