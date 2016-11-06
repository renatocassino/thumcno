<?php

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ConfigCommand extends Command
{
    protected function configure()
    {
        $this
          // the name of the command (the part after "bin/console")
          ->setName('config')

          // the short description shown while running "php bin/console list"
          ->setDescription('Make an initial configuration to use Thumcno.')

          // the full command description shown when running the command with
          // the "--help" option
          ->setHelp("This create an initial configuration to Thumcno.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $output->writeln('');
        $output->writeln('  ████████╗██╗  ██╗██╗   ██╗███╗   ███╗ ██████╗███╗   ██╗ ██████╗ ');
        $output->writeln('  ╚══██╔══╝██║  ██║██║   ██║████╗ ████║██╔════╝████╗  ██║██╔═══██╗');
        $output->writeln('     ██║   ███████║██║   ██║██╔████╔██║██║     ██╔██╗ ██║██║   ██║');
        $output->writeln('     ██║   ██╔══██║██║   ██║██║╚██╔╝██║██║     ██║╚██╗██║██║   ██║');
        $output->writeln('     ██║   ██║  ██║╚██████╔╝██║ ╚═╝ ██║╚██████╗██║ ╚████║╚██████╔╝');
        $output->writeln('     ╚═╝   ╚═╝  ╚═╝ ╚═════╝ ╚═╝     ╚═╝ ╚═════╝╚═╝  ╚═══╝ ╚═════╝ ');
        $output->writeln('                                           The thumbnail generator');
        $output->writeln('                                                       By Tacnoman');
        $output->writeln('');                                                   

        # ENV
        $output->writeln('Configuring the environment variables:');
        $question = new Question('Enter the thumcno_path (keep in blank if you want to use the root path): <info>['.dirname(__DIR__).']</info>: ', false);
        $thumcnoPath = $helper->ask($input, $output, $question);

        $question = new ChoiceQuestion('Permit use for only domain? ', ['no','yes'], 0 );
        $question->setErrorMessage('Answer `%s` is invalid.');
        $useForOnlyDomain = $helper->ask($input, $output, $question);

        $envFileContent = '';
        if($thumcnoPath) {
            $envFileContent .= 'THUMCNO_PATH=' . rtrim($thumcnoPath, '/') . PHP_EOL;
        }
        $envFileContent .= 'PERMIT_ONLY_DOMAIN=';
        $envFileContent .= ($useForOnlyDomain == 'yes') ? '1' : '0';

        $arq = fopen(dirname(__DIR__).'/.env', 'w');
        fwrite($arq, $envFileContent);
        fclose($arq);
    }
}

$application->add(new ConfigCommand());