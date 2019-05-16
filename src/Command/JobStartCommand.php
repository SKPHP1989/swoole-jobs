<?php

namespace Michael\Jobs\Command;

use Michael\Jobs\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobStartCommand extends BaseCommand
{
    protected static $defaultName = 'job:start';

    protected function configure()
    {
        $this->setDescription('Create a job server');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $outputObj = Utils::app()->get('output');
        $outputObj->info("Start up server,please hold on");
        $consoleObj = Utils::app()->get('console');
        $consoleObj->start();
    }
}