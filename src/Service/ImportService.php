<?php

namespace KJanczyk\PimcoreSOMLBundle\Service;

use KJanczyk\PimcoreSOMLBundle\Provider\ProviderInterface;

class ImportService
{
    private array $providers;

    public function __construct(
        iterable $providers
    )
    {
        $this->providers = [];
        foreach ($providers as $provider) {
            if ($provider instanceof ProviderInterface) {
                $this->providers[$provider::PLATFORM] = $provider;
            }
        }
    }

    public function import(string $platform): int
    {
        foreach ($this->providers as $provider) {
            if ($platform !== 'all' && $provider::PLATFORM !== $platform) {
                continue;
            }

            if (!$provider->isConfigured()) {
                throw new \Exception('Provider for ' . $provider::PLATFORM . ' is not configured.');
            }

            $posts = $provider->fetchPosts();
            return count($posts);
        }

        return 0;
    }
}
