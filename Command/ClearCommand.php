<?php

namespace WebmanTech\LaravelCache\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use WebmanTech\LaravelCache\Facades\Cache;

class ClearCommand extends Command
{
    protected static $defaultName = 'cache:clear';
    protected static $defaultDescription = 'Flush the application cache';

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('store', InputArgument::OPTIONAL, 'The name of the store you would like to clear');
        $this->addOption('tags', null, InputOption::VALUE_OPTIONAL, 'The cache tags you would like to clear');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var QuestionHelper $questionHelper */
        $questionHelper = $this->getHelper('question');
        $question = new Question(
            <<<TXT
Flushing the cache does not respect your configured cache "prefix" and will remove all entries from the cache.
Consider this carefully when clearing a cache which is shared by other applications.
Confirm? (y/n)
TXT
            , false);
        if (!$questionHelper->ask($input, $output, $question)) {
            $output->writeln('Stopped');
            return self::SUCCESS;
        }

        $cache = Cache::instance()->store($input->getArgument('store'));
        $tags = array_filter(explode(',', $input->getOption('tags') ?? ''));
        if ($tags) {
            $cache->tags($tags);
        }

        $successful = $cache->flush();

        if (!$successful) {
            $output->writeln('Failed to clear cache. Make sure you have the appropriate permissions.');
            return self::FAILURE;
        }

        $output->writeln('Application cache cleared successfully.');
        return self::SUCCESS;
    }
}
