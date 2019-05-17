<?php

namespace Michael\Jobs\Command;

use Michael\Jobs\Utils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class JobStopCommand extends BaseCommand
{
    protected static $defaultName = 'job:stop';

    protected function configure()
    {
        $this->setDescription('Stop current a job server');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);
        $consoleObj = Utils::app()->get('console');
        $consoleObj->stop();
    }
}