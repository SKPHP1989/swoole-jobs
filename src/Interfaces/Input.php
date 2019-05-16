<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Input
{
    public function getArgument($name = '');

    public function getOption($option = '');
}