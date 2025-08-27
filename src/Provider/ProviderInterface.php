<?php
declare(strict_types=1);

namespace Muz\Pimcore\SoMLBundle\Provider;

use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;

/**
 * Common contract for social media providers. Allows checking configuration,
 * fetching posts from a platform, and downloading associated media.
 */
interface ProviderInterface
{
    public const PLATFORM = '';

    /**
     * Check whether the provider has all required credentials/configuration set.
     * Implementations should return true only when it is safe to call other methods.
     *
     * @return bool True if the provider is ready to be used.
     */
    public function isConfigured(): bool;

    /**
     * Fetch posts from the underlying platform and map them to SocialMediaPostDto.
     * Implementations may cap the limit according to platform/API constraints.
     *
     * @param int $limit Maximum number of posts to retrieve.
     * @return array<SocialMediaPostDto> List of mapped posts.
     */
    public function fetchPosts(int $limit): array;

    /**
     * Download media files associated with the given post into the target directory.
     * Implementations may set a 'local_path' field on media items to point to the stored file.
     *
     * @param SocialMediaPostDto $post Post whose media should be downloaded.
     * @param string $targetDir Absolute or relative path to the target directory.
     * @return void
     */
    public function downloadMedia(SocialMediaPostDto $post, string $targetDir): void;
}
