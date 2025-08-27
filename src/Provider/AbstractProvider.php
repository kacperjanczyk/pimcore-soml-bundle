<?php

namespace Muz\Pimcore\SoMLBundle\Provider;

use GuzzleHttp\Client;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Psr\Log\LoggerInterface;
use Symfony\Component\Filesystem\Filesystem;

abstract class AbstractProvider implements ProviderInterface
{
    protected LoggerInterface $logger;
    protected Client $httpClient;
    protected Filesystem $filesystem;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->httpClient = new Client([
            'verify' => false,
            'timeout' => 30,
        ]);
        $this->filesystem = new Filesystem();
    }

    /**
     * Generate a unique filename for media
     *
     * @param SocialMediaPostDto $post
     * @param string $extension
     * @param int $index
     * @return string
     */
    protected function generateMediaFilename(SocialMediaPostDto $post, string $extension, int $index = 0): string
    {
        return sprintf(
            '%s_%s_%s_%d.%s',
            $post->platform,
            $post->externalId,
            date('Ymd'),
            $index,
            $extension
        );
    }

    /**
     * Download a file from URL to a local path
     *
     * @param string $url
     * @param string $targetPath
     * @return bool
     */
    protected function downloadFile(string $url, string $targetPath): bool
    {
        try {
            $directory = dirname($targetPath);
            if (!file_exists($directory)) {
                $this->filesystem->mkdir($directory, 0755);
            }

            $response = $this->httpClient->get($url, ['sink' => $targetPath]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            $this->logger->error('Failed to download file', [
                'url' => $url,
                'target' => $targetPath,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Extract extension from URL
     *
     * @param string $url
     * @param string $default
     * @return string
     */
    protected function getExtensionFromUrl(string $url, string $default = 'jpg'): string
    {
        $parts = parse_url($url);
        if (!isset($parts['path'])) {
            return $default;
        }

        $extension = pathinfo($parts['path'], PATHINFO_EXTENSION);
        return !empty($extension) ? strtolower($extension) : $default;
    }
}
