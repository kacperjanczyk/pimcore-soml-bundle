<?php

namespace Muz\Pimcore\SoMLBundle\Provider;

use GuzzleHttp\Exception\GuzzleException;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Psr\Log\LoggerInterface;

class LinkedInProvider extends AbstractProvider
{
    private string $clientId;
    private string $clientSecret;
    private string $organizationId;
    private ?string $accessToken = null;
    private string $apiVersion = '202405';

    public function __construct(
        string $linkedinClientId,
        string $linkedinClientSecret,
        string $linkedinOrganizationId,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->clientId = $linkedinClientId;
        $this->clientSecret = $linkedinClientSecret;
        $this->organizationId = $linkedinOrganizationId;
    }

    /**
     * Authenticates with the LinkedIn API using the Client Credential Flow (2-legged OAuth).
     * This is called only when needed.
     */
    private function authenticate(): bool
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            $this->logger->error('LinkedIn Client ID or Client Secret is not configured.');
            return false;
        }

        try {
            $response = $this->httpClient->post('https://www.linkedin.com/oauth/v2/accessToken', [
                'form_params' => [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['access_token'])) {
                $this->accessToken = $data['access_token'];
                return true;
            } else {
                $this->logger->error('Failed to retrieve LinkedIn access token.', ['response' => $data]);
                return false;
            }

        } catch (GuzzleException $e) {
            $this->logger->error('Failed to authenticate with LinkedIn API.', [
                'error' => $e->getMessage(),
                'response' => $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : 'No response body'
            ]);
            return false;
        }
    }

    public function getPlatform(): string
    {
        return SocialMediaPostDto::PLATFORM_LINKEDIN;
    }

    public function isConfigured(): bool
    {
        // Configuration is valid if credentials are set. Token is checked later.
        return !empty($this->clientId) && !empty($this->clientSecret) && !empty($this->organizationId);
    }

    /**
     * @return array<string, string>
     */
    private function getHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->accessToken,
            'LinkedIn-Version' => $this->apiVersion,
            'X-Restli-Protocol-Version' => '2.0.0',
            'Content-Type' => 'application/json',
        ];
    }

    public function fetchPosts(int $limit): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('LinkedIn provider is not configured with required credentials.');
            return [];
        }

        // Lazy authentication: only get a token if we don't have one.
        if (empty($this->accessToken)) {
            $this->logger->info('LinkedIn Access Token not found. Attempting to authenticate.');
            if (!$this->authenticate()) {
                $this->logger->error('LinkedIn authentication failed. Cannot fetch posts.');
                return [];
            }
        }

        try {
            // The new Posts API endpoint
            $response = $this->httpClient->get('https://api.linkedin.com/rest/posts', [
                'headers' => $this->getHeaders(),
                'query' => [
                    'author' => 'urn:li:organization:' . $this->organizationId,
                    'q' => 'author',
                    'count' => min($limit, 100),
                    'fields' => 'id,author,commentary,content,created,likesSummary,commentsSummary,repostsSummary'
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['elements']) || empty($data['elements'])) {
                return [];
            }

            $posts = [];

            foreach ($data['elements'] as $linkedinPost) {
                $post = new SocialMediaPostDto();
                $post->platform = $this->getPlatform();
                $post->externalId = $linkedinPost['id'] ?? '';
                $post->id = md5($this->getPlatform() . '_' . $post->externalId);

                // Extract content
                $post->content = $linkedinPost['commentary'] ?? '';

                // Format date from the 'created' object
                if (isset($linkedinPost['created']['time'])) {
                    $timestamp = intval($linkedinPost['created']['time'] / 1000); // Convert from milliseconds
                    $post->publishedAt = new \DateTime("@{$timestamp}");
                } else {
                    $post->publishedAt = new \DateTime();
                }

                // Construct post URL
                $post->url = "https://www.linkedin.com/feed/update/{$post->externalId}";

                // Extract social metrics directly from the post data
                $post->likeCount = $linkedinPost['likesSummary']['totalLikes'] ?? 0;
                $post->commentCount = $linkedinPost['commentsSummary']['totalComments'] ?? 0;
                $post->shareCount = $linkedinPost['repostsSummary']['totalReposts'] ?? 0;


                // Extract media content from the 'content' object
                if (isset($linkedinPost['content']['media'])) {
                    $mediaItems = $linkedinPost['content']['media'];

                    foreach ($mediaItems as $mediaItem) {
                        // Simplified media type detection, assuming 'IMAGE' or 'VIDEO'
                        $mediaType = 'image';
                        if (str_contains($mediaItem['type'], 'VIDEO')) {
                            $mediaType = 'video';
                        }

                        $mediaUrl = $mediaItem['url'] ?? null;

                        if ($mediaUrl) {
                            $post->addMedia($mediaType, $mediaUrl);
                        }
                    }
                }

                // Extract hashtags and mentions from commentary
                if (!empty($post->content)) {
                    preg_match_all('/#(\w+)/', $post->content, $hashtagMatches);
                    if (!empty($hashtagMatches[1])) {
                        $post->hashtags = $hashtagMatches[1];
                    }

                    preg_match_all('/@\[(.*?)\]/', $post->content, $mentionMatches);
                    if (!empty($mentionMatches[1])) {
                        $post->mentions = $mentionMatches[1];
                    }
                }

                $posts[] = $post;
            }

            return $posts;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch LinkedIn posts', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function downloadMedia(SocialMediaPostDto $post, string $targetDir): void
    {
        if (!$post->hasMedia()) {
            return;
        }

        foreach ($post->media as $index => &$mediaItem) {
            $extension = $this->getExtensionFromUrl($mediaItem['url'], 'jpg');
            $filename = $this->generateMediaFilename($post, $extension, $index);
            $targetPath = $targetDir . '/' . $filename;

            if ($this->downloadFile($mediaItem['url'], $targetPath)) {
                $mediaItem['local_path'] = $targetPath;
            }
        }
    }
}
