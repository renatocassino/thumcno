<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class ClearCacheCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('clear:cache')

          // the short description shown while running "php bin/console list"
          ->setDescription('Clear cache for all images.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp("Clear the directory cache.");
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
            unlink($file);
            $output->write('<comment>.</comment>');
        }

        $output->writeln(PHP_EOL.'<info>Successfully! Cache cleared :D</info>'.PHP_EOL);
        $numberOfFiles = count($files) - 1;
        $output->writeln('Files deleted: <comment>' . $numberOfFiles . '</comment>');
        $output->writeln('Cleared: <comment>' . $this->humanFilesize($fileSize) . '</comment>.');
    }

    private function humanFilesize($bytes, $decimals = 2) {
        $sz = 'BKMGTP';
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }
}

$application->add(new ClearCacheCommand());