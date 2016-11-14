<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckCommand extends Command
{
    protected function configure()
    {
        $this
          ->setName('check')
          ->setDescription('Check requirements to use thumcno.')
          ->setHelp('This command check if all is ok to use the application.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Checking configurations...');
        $output->write(PHP_EOL);
        $errors = 0;

        if (file_exists(dirname(__DIR__).'/vendor/autoload.php')) {
            $output->writeln(' <info>✔</info> Composer installed');
        } else {
            $output->writeln(' <fg=red>x</> You must run `composer install`');
            ++$errors;
        }

        if (extension_loaded('gd') && function_exists('gd_info')) {
            $output->writeln(' <info>✔</info> PHP-GD installed');
        } else {
            $output->writeln(' <fg=red>x</> You must install PHP-GD.');
            ++$errors;
        }

        if (file_exists(dirname(__DIR__).'/.env')) {
            $output->writeln(' <info>✔</info> File .env created');
        } else {
            $output->writeln(' <fg=red>x</> File .env does not exists. Run `php thumcno config` to create the file.');
            ++$errors;
        }

        $cacheDir = dirname(__DIR__).'/cache';
        if (is_dir($cacheDir)) {
            $output->writeln(' <info>✔</info> The folder cache was created');

            $permission = substr(sprintf('%o', fileperms($cacheDir)), -4);
            if (is_writable($cacheDir)) {
                $output->writeln(' <info>✔</info> The folder cache has the correct permissions');
            } else {
                $output->writeln(' <fg=red>x</> The folder `/cache` doesnt have the correct permissions. Run `chmod 777 cache` to fix this');
                $output->writeln($permission);
                ++$errors;
            }
        } else {
            $output->writeln(' <fg=red>x</> You must create the cache folder. Run `php thumcno config` to create the folder.');
            ++$errors;
        }

        if (0 == $errors) {
            $output->writeln(PHP_EOL.'<info>Congrats! Now you can start the server and use the application :D</info>');
            $output->writeln('You can run <comment>php thumcno server</comment> to start'.PHP_EOL);
        } else {
            $output->writeln(PHP_EOL.' <fg=red>You have '.$errors.' error(s) to fix</fg>'.PHP_EOL);
        }
    }
}

$application->add(new CheckCommand());
