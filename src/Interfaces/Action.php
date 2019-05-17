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
    /**
     * set auto load path
     * @param string $path
     * @return mixed
     */
    public function setAutoloadPath($path = '');

    /**
     * set app path
     * @param string $path
     * @return mixed
     */
    public function setAppPath($path = '');

    /**
     * @param Task $task
     * @return mixed
     */
    public function start($task);
}