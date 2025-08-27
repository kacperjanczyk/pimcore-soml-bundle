<?php

namespace Muz\Pimcore\SoMLBundle\Provider;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Psr\Log\LoggerInterface;

/**
 * Facebook provider: fetches page feed via Graph API, maps items to SocialMediaPostDto
 * and handles downloading attached media.
 */
class FacebookProvider extends AbstractProvider
{
    public const PLATFORM = SocialMediaPostDto::PLATFORM_FACEBOOK;
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';
    private string $appId;
    private string $appSecret;
    private string $pageId;
    private string $facebookUserAccessToken;

    /**
     * @param string $facebookAppId Facebook application ID.
     * @param string $facebookAppSecret Facebook application secret.
     * @param string $facebookPageId Facebook Page ID.
     * @param string $facebookUserAccessToken User Access Token used to obtain the Page Access Token.
     * @param LoggerInterface $logger PSR-3 logger instance.
     */
    public function __construct(
        string $facebookAppId,
        string $facebookAppSecret,
        string $facebookPageId,
        string $facebookUserAccessToken,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->appId = $facebookAppId;
        $this->appSecret = $facebookAppSecret;
        $this->pageId = $facebookPageId;
        $this->facebookUserAccessToken = $facebookUserAccessToken;
    }

    /**
     * Checks whether the provider is configured with required credentials/identifiers.
     *
     * @return bool True if all required values are present.
     */
    public function isConfigured(): bool
    {
        return !empty($this->appId) && !empty($this->appSecret) && !empty($this->pageId) && !empty($this->facebookUserAccessToken);
    }

    /**
     * Fetches posts from the Facebook page feed and maps them to SocialMediaPostDto.
     * The limit is capped at 100 per request (API constraint).
     *
     * @param int $limit Maximum number of posts to fetch.
     * @return SocialMediaPostDto[] List of mapped posts.
     * @throws Exception When Page Access Token cannot be obtained, feed is empty, or response status is invalid.
     * @throws GuzzleException On HTTP communication errors.
     */
    public function fetchPosts(int $limit): array
    {
        $pageAccessToken = $this->getPageAccessToken($this->facebookUserAccessToken);
        if (!$pageAccessToken) {
            $this->logger->warning('FacebookProvider: Unable to retrieve Page Access Token.');
            throw new Exception('Unable to retrieve Facebook Page Access Token.');
        }

        $feedRequest = $this->httpClient->get(self::GRAPH_BASE_URL . '/' . $this->pageId . '/feed', [
            'query' => [
                'access_token' => $pageAccessToken,
                'fields' => 'id,message,created_time,permalink_url,attachments{media_type,media,url},likes.limit(0).summary(true),comments.limit(0).summary(true),shares',
                'limit' => min($limit, 100)
            ]
        ]);

        if ($feedRequest->getStatusCode() !== 200) {
            throw new Exception('Failed to fetch Facebook feed. Status code: ' . $feedRequest->getStatusCode());
        }
        $feed = json_decode($feedRequest->getBody()->getContents(), true);
        if (empty($feed['data'])) {
            $this->logger->info('FacebookProvider: No posts found in the feed response.');
            throw new Exception('No posts found in Facebook feed.');
        }

        $posts = [];
        foreach ($feed['data'] as $feedItem) {
            $post = new SocialMediaPostDto();
            $post->platform = self::PLATFORM;
            $post->externalId = $feedItem['id'] ?? '';
            $post->id = md5(self::PLATFORM . '_' . ($feedItem['id'] ?? ''));
            $post->content = $feedItem['message'] ?? '';
            $post->publishedAt = new DateTime($feedItem['created_time'] ?? 'now');
            $post->url = $feedItem['permalink_url'] ?? null;
            $post->likeCount = $feedItem['likes']['summary']['total_count'] ?? 0;
            $post->commentCount = $feedItem['comments']['summary']['total_count'] ?? 0;
            $post->shareCount = $feedItem['shares']['count'] ?? 0;
            $post->hashtags = $this->getHashtagsFromContent($post->content);

            if (!empty($feedItem['attachments']['data'])) {
                foreach ($feedItem['attachments']['data'] as $attachment) {
                    if (isset($attachment['media_type'], $attachment['media']['image']['src'])) {
                        $mediaType = in_array($attachment['media_type'], ['video', 'animated_image_video'], true) ? 'video' : 'image';
                        $post->addMedia(
                            $mediaType,
                            $attachment['media']['image']['src']
                        );
                    }
                }
            }

            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Downloads media attached to the given post and sets local file paths for them.
     * Each media file is saved to the target directory with a unique file name.
     *
     * Side effect: populates 'local_path' for each item in $post->media.
     *
     * @param SocialMediaPostDto $post Post whose media should be downloaded.
     * @param string $targetDir Target directory where files will be stored.
     * @return void
     */
    public function downloadMedia(SocialMediaPostDto $post, string $targetDir): void
    {
        if (!$post->hasMedia()) {
            return;
        }

        foreach ($post->media as $index => &$mediaItem) {
            $extension = $this->getExtensionFromUrl($mediaItem['url']);
            $filename = $this->generateMediaFilename($post, $extension, $index);
            $targetPath = $targetDir . '/' . $filename;

            if ($this->downloadFile($mediaItem['url'], $targetPath)) {
                $mediaItem['local_path'] = $targetPath;
            }
        }
    }

    /**
     * Obtains a Page Access Token for the configured Page ID using a User Access Token.
     *
     * @param string $userAccessToken User Access Token.
     * @return string|null Page Access Token or null if it cannot be retrieved.
     */
    private function getPageAccessToken(string $userAccessToken): ?string
    {
        try {
            $response = $this->httpClient->get(self::GRAPH_BASE_URL . '/' . $this->pageId, [
                'query' => [
                    'access_token' => $userAccessToken,
                    'fields' => 'access_token'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                return null;
            }
            $response = json_decode($response->getBody()->getContents(), true);

            if (is_array($response) && isset($response['access_token'])) {
                return $response['access_token'];
            }
        } catch (GuzzleException $e) {
            $this->logger->error('FacebookProvider: Failed to get page access token', ['exception' => $e]);
        }

        return null;
    }

    /**
     * Extracts hashtags from the given content. Returns tags without the '#'.
     * Supports Unicode characters (letters, marks, digits, underscore and hyphen).
     *
     * @param string $content Post content.
     * @return string[] List of hashtags without '#'.
     */
    private function getHashtagsFromContent(string $content): array
    {
        if (!empty($content)) {
            if (preg_match_all('/#([\p{L}\p{Mn}\p{Nd}_-]+)/u', $content, $hashtagMatches) && !empty($hashtagMatches[1])) {
                return $hashtagMatches[1];
            }
        }

        return [];
    }
}
