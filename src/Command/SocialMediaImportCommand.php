<?php

namespace KJanczyk\PimcoreSOMLBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'soml:import',
    description: 'Import social media feed'
)]
class SocialMediaImportCommand extends AbstractCommand
{
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Social Media Import Command');

        $io->success('Done.');

        return Command::SUCCESS;
    }
}
