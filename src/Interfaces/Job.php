<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 17:33
 */

namespace Michael\Jobs\Interfaces;
interface Job
{
    public function setTopic($topic);

    public function run();

    public function getWaitingAmount();

    public function clear();
}