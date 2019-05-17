<?php

namespace Michael\Jobs\Command;

use Michael\Jobs\Input\SymConsoleInput;
use Michael\Jobs\Input\SymInput;
use Michael\Jobs\Output\SymOutput;
use Michael\Jobs\Utils;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BaseCommand extends Command
{
    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Utils::registerConsoleProvider();
        $provider = Utils::getProvider();
        $provider->setContainer(Utils::app());
        $provider->registerCore(Utils::getConfig());
        $provider->registerSingleton('input',function () use ($input) {
            return new SymInput($input);
        });
        $provider->registerSingleton('output',function () use ($output) {
            return new SymOutput($output);
        });
    }
}