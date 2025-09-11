<?php

namespace KJanczyk\PimcoreSOMLBundle\Provider;

use Exception;

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
        parent::__construct();
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
     * @throws Exception
     */
    public function fetchPosts(): array
    {
        $pageAccessToken = $this->getPageAccessToken();
        if (!$pageAccessToken) {
            throw new Exception('Failed to obtain Facebook Page Access Token.');
        }

        $feedRequestResponse = $this->makeAPIRequest('/feed', [
            'query' => [
                'access_token' => $pageAccessToken,
                'fields' => 'id,message,created_time,attachments{media},permalink_url',
                'limit' => 25
            ]
        ]);

        if (!$feedRequestResponse || !isset($feedRequestResponse['data'])) {
            throw new Exception('Failed to fetch Facebook page feed.');
        }

        if (empty($feedRequestResponse['data'])) {
            throw new Exception('Facebook page feed is empty.');
        }

        return $feedRequestResponse['data'];
    }

    /**
     * @inheritDoc
     */
    public function downloadMedia()
    {
        // TODO: Implement downloadMeida() method.
    }

    private function getPageAccessToken(): ?string
    {
        $response = $this->makeAPIRequest('', [
            'query' => [
                'access_token' => $this->accessToken,
                'fields' => 'access_token'
            ]
        ]);

        if (is_array($response) && isset($response['access_token'])) {
            return $response['access_token'];
        }

        return null;
    }

    private function makeAPIRequest(string $endpoint, array $query): ?array
    {
        $response = $this->httpClient->get(self::GRAPH_BASE_URL . '/' . $this->pageId . $endpoint, $query);

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        return json_decode($response->getBody()->getContents(), true);
    }
}
