<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class RemoveDomainCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('domain:remove')

          // the short description shown while running "php bin/console list"
          ->setDescription('Remove an exist domain file.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp("This command remove an exist configuration for a domain.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $question = new Question('Please enter the domain to remove: <info>(without http://)</info>: ', '');
        $domain = $helper->ask($input, $output, $question);

        $question = new Question('Are you sure delete the domain <comment>'.$domain.'</comment> (yes|no): ', false, '/^(y|n)/i');
        $answer = $helper->ask($input, $output, $question);
        if ($answer == 'no' || $answer == 'n' || !$answer) {
            return;
        }

        $path = $_ENV['THUMCNO_PATH'].'/apps/'.$domain.'.ini';
        if(!file_exists($path)) {
            $output->writeln('<fg=red>ERROR: The domain does not exist. Path: '.$path.'</>'.PHP_EOL);
            return;
        }

        unlink($path);

        $output->writeln(PHP_EOL);
        $output->writeln('================');
        $output->writeln('You removed the file: <fg=red>'.$path.'</>.');
        $output->writeln(PHP_EOL);
    }
}

$application->add(new RemoveDomainCommand());