<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ServeCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('server')

          // the short description shown while running "php bin/console list"
          ->setDescription('Create a server in localhost.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp("Create a server to run in localhost.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = getenv('PORT');
        if (!$port) {
            $port = 8888;
        }
        $output->writeln(PHP_EOL);
        $output->writeln('Listening on: <info>http://0.0.0.0:'.$port.'</info>');

        exec('php -S 0.0.0.0:'.$port.' '.dirname(__DIR__).'/index.php');
        $output->writeln(PHP_EOL);
    }
}

$application->add(new ServeCommand());