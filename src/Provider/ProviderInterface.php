<?php
declare(strict_types=1);

namespace KJanczyk\PimcoreSOMLBundle\Provider;

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
     */
    public function fetchPosts(): array;

    /**
     * Download media files associated with the given post into the target directory.
     * Implementations may set a 'local_path' field on media items to point to the stored file.
     *
     */
    public function downloadMedia();
}
