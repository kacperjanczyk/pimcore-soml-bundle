<?php

namespace Muz\Pimcore\SoMLBundle\Command;

use Muz\Pimcore\SoMLBundle\Service\SocialMediaService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportSocialMediaCommand extends Command
{
    protected static $defaultName = 'muz:social-media:import';
    protected static $defaultDescription = 'Import social media posts from configured platforms';

    private SocialMediaService $socialMediaService;
    private LoggerInterface $logger;

    public function __construct(
        SocialMediaService $socialMediaService,
        LoggerInterface $logger
    ) {
        parent::__construct();
        $this->socialMediaService = $socialMediaService;
        $this->logger = $logger;
    }

    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addOption(
                'platform',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specify platform to import (twitter, instagram, facebook, linkedin). All platforms will be used if not specified.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Importing social media posts');
        $platform = $input->getOption('platform');

        try {
            $startTime = microtime(true);
            $count = $this->socialMediaService->importPosts($platform);
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);

            $io->success(sprintf(
                'Successfully imported %d social media posts%s in %s seconds',
                $count,
                $platform ? " from $platform" : '',
                $duration
            ));

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->logger->error('Error during social media import: ' . $e->getMessage());
            $io->error('Error during social media import: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
