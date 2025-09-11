<?php

namespace KJanczyk\PimcoreSOMLBundle\Command;

use KJanczyk\PimcoreSOMLBundle\Service\ImportService;
use Pimcore\Console\AbstractCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'soml:import',
    description: 'Import social media feed'
)]
class SocialMediaImportCommand extends AbstractCommand
{
    private ImportService $importService;
    private LoggerInterface $logger;

    public function __construct(
        ImportService $importService,
        LoggerInterface $logger
    )
    {
        parent::__construct();

        $this->importService = $importService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'platform',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specify platform to import (facebook, instagram). All platforms will be used if not specified.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Social Media Import Command');
        $platform = $input->getOption('platform');
        $io->info('Fetching posts from ' . ($platform ?? 'all platforms'));

        try {
            $importCount = $this->importService->import($platform ?? 'all');
            $io->success('Import completed successfully. Total items imported: ' . $importCount);
        } catch (\Exception $e) {
            $errorContext = [
                'command' => 'soml:import',
                'platform' => $platform ?? 'all',
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ];

            $errorMessage = sprintf(
                'Import failed: %s',
                $e->getMessage()
            );

            $this->logger->error($errorMessage, $errorContext);
            $io->error($errorMessage);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
