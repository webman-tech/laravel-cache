<?php

namespace WebmanTech\LaravelCache\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use WebmanTech\LaravelCache\Facades\Cache;

class ForgetCommand extends Command
{
    protected static $defaultName = 'cache:forget';
    protected static $defaultDescription = 'Remove an item from the cache';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('key', InputArgument::REQUIRED, 'The key to remove');
        $this->addArgument('store', InputArgument::OPTIONAL, 'The store to remove the key from');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        Cache::instance()->store($input->getArgument('store'))->forget($key);

        $output->writeln('The [' . $key . '] key has been removed from the cache.');

        return self::SUCCESS;
    }

}
