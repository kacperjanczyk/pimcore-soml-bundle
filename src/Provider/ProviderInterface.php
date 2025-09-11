<?php

namespace KJanczyk\PimcoreSOMLBundle\Provider;

interface ProviderInterface
{
    public const PLATFORM = '';
    
    /**
     * Check if the provider is configured
     *
     * @return bool
     */
    public function isConfigured(): bool;

    /**
     * Fetch posts from the platform
     *
     * @return array
     */
    public function fetchPosts();

    /**
     * Download media for a post
     *
     * @return void
     */
    public function downloadMedia();
}
