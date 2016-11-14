<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class AddDomainCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('domain:add')

          // the short description shown while running "php bin/console list"
          ->setDescription('Adding a domain file.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp('This command add a new configuration for a domain.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the domain: <info>(without http://)</info>: ', '');
        $domain = $helper->ask($input, $output, $question);

        $question = new Question('Enter the port: <info>Default: 80</info>: ', '80');
        $port = $helper->ask($input, $output, $question);

        $fileContent = file_get_contents(__DIR__.'/data/localhost.default.ini');
        $fileContent = str_replace('{port}', $port, $fileContent);

        $arq = fopen($_ENV['THUMCNO_PATH'].'/apps/'.$domain.'.ini', 'w');
        fwrite($arq, $fileContent);
        fclose($arq);

        $output->writeln(PHP_EOL);
        $output->writeln('<info>Created the configuration for domain '.$domain.'</info>');
        $output->writeln('================');
        $output->writeln('You can configure the file in <comment>'.$_ENV['THUMCNO_PATH'].'/apps/'.$domain.'.ini</comment>.');
        $output->writeln(PHP_EOL);
    }
}

$application->add(new AddDomainCommand());
