<?php
/**
 * Created by PhpStorm.
 * User: lij25
 * Date: 2019/5/9
 * Time: 16:19
 */

namespace Michael\Jobs\Output;


use Michael\Jobs\Interfaces\Output;

class SymOutput implements Output
{
    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $outputDriver;

    public function __construct($outputDriver = '')
    {
        $this->outputDriver = $outputDriver;
    }

    public function error(string $msg)
    {
        $this->outputDriver->writeln(sprintf('<error>%s</error>', $msg));
    }

    public function info(string $msg)
    {
        $this->outputDriver->writeln(sprintf('<info>%s</info>', $msg));
    }

    public function warn(string $msg)
    {
        $this->outputDriver->writeln(sprintf('<comment>%s</comment>', $msg));
    }
}