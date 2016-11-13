<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class CacheInfoCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('cache:info')

          // the short description shown while running "php bin/console list"
          ->setDescription('Get informations about cache.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp("Show number of files and size.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = glob($_ENV['THUMCNO_PATH'].'/cache/*');
        $fileSize = 0;

        foreach($files as $file) {
            if(basename($file) == 'index.html') {
                continue;
            }
            $fileSize += filesize($file);
        }

        $numberOfFiles = count($files)-1;
        $output->writeln(PHP_EOL.'Cache information'.PHP_EOL);
        $output->writeln('Files cache: <comment>' . $numberOfFiles . '</comment>');
        $output->writeln('Size of cache files: <comment>' . humanFilesize($fileSize) . '</comment>.');
    }
}

$application->add(new CacheInfoCommand());