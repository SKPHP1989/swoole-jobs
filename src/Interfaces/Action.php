<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Action
{
    public function setAutoloadPath($path='');

    public function setAppPath($path='');

    /**
     * @param Task $task
     * @return mixed
     */
    public function start($task);
}