<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Input;


use Michael\Jobs\Interfaces\Input;

class SymInput implements Input
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $inputDriver;

    public function __construct($inputDriver)
    {
        $this->inputDriver = $inputDriver;
    }

    public function getArgument($name = '')
    {
        if (empty($name)) {
            return $this->inputDriver->getArguments();
        }
        return $this->inputDriver->getArgument($name);
    }

    public function getOption($option = '')
    {
        if (empty($option)) {
            return $this->inputDriver->getOptions();
        }
        return $this->inputDriver->getOption($option);
    }
}