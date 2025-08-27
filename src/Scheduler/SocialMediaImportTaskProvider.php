<?php

namespace Muz\Pimcore\SoMLBundle\Scheduler;

use Muz\Pimcore\SoMLBundle\Service\SocialMediaService;
use Pimcore\Maintenance\TaskInterface;
use Psr\Log\LoggerInterface;

class SocialMediaImportTaskProvider implements TaskInterface
{
    private SocialMediaService $socialMediaService;
    private LoggerInterface $logger;

    public function __construct(
        SocialMediaService $socialMediaService,
        LoggerInterface $logger
    ) {
        $this->socialMediaService = $socialMediaService;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        $this->logger->info('Starting scheduled social media import');
        
        try {
            $count = $this->socialMediaService->importPosts();
            $this->logger->info('Scheduled social media import completed successfully', ['count' => $count]);
        } catch (\Exception $e) {
            $this->logger->error('Error during scheduled social media import', [
                'message' => $e->getMessage(),
                'exception' => $e
            ]);
        }
    }
}