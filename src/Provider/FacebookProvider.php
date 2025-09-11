<?php

namespace KJanczyk\PimcoreSOMLBundle\Provider;

use KJanczyk\PimcoreSOMLBundle\Provider\AbstractProvider;

class FacebookProvider extends AbstractProvider
{
    public const PLATFORM = 'facebook';
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';
    private string $accessToken;
    private string $appId;
    private string $appSecret;
    private string $pageId;

    public function __construct(
        string $facebookAccessToken,
        string $facebookAppId,
        string $facebookAppSecret,
        string $facebookPageId,
    ) {
        $this->accessToken = $facebookAccessToken;
        $this->appId = $facebookAppId;
        $this->appSecret = $facebookAppSecret;
        $this->pageId = $facebookPageId;
    }

    /**
     * @inheritDoc
     */
    public function isConfigured(): bool
    {
        return !empty($this->accessToken) && !empty($this->appId) && !empty($this->appSecret) && !empty($this->pageId);
    }

    /**
     * @inheritDoc
     */
    public function fetchPosts(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function downloadMedia()
    {
        // TODO: Implement downloadMedia() method.
    }
}
