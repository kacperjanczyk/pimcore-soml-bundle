<?php
declare(strict_types=1);

namespace Muz\Pimcore\SoMLBundle\Provider;

use DateTime;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Psr\Log\LoggerInterface;

/**
 * Instagram provider: fetches media via Graph API, maps items to SocialMediaPostDto
 * and handles downloading attached media files.
 */
class InstagramProvider extends AbstractProvider
{
    public const PLATFORM = SocialMediaPostDto::PLATFORM_INSTAGRAM;
    private const GRAPH_BASE_URL = 'https://graph.facebook.com';
    private string $userId;
    private string $userAccessToken;

    /**
     * @param string $instagramUserId Instagram Business/Creator account ID (may be unused when using /me/media endpoint).
     * @param string $facebookUserAccessToken Facebook User Access Token with permissions to read Instagram media.
     * @param LoggerInterface $logger PSR-3 logger instance.
     */
    public function __construct(
        string $instagramUserId,
        string $facebookUserAccessToken,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->userId = $instagramUserId;
        $this->userAccessToken = $facebookUserAccessToken;
    }

    /**
     * Check whether required configuration values are present.
     *
     * @return bool True if the provider is ready to be used.
     */
    public function isConfigured(): bool
    {
        return !empty($this->userId) && !empty($this->userAccessToken);
    }

    /**
     * Fetch media from Instagram Graph API and map them to SocialMediaPostDto objects.
     * The limit is capped to a maximum of 100 per request due to API constraints.
     *
     * @param int $limit Maximum number of posts to fetch.
     * @return array<SocialMediaPostDto> List of mapped posts.
     * @throws GuzzleException On HTTP communication errors.
     * @throws Exception When response status is invalid or no posts are found.
     */
    public function fetchPosts(int $limit): array
    {
        $feedResponse = $this->httpClient->get(self::GRAPH_BASE_URL . '/' . $this->userId . '/media', [
            'query' => [
                'fields' => 'id,caption,media_type,media_url,thumbnail_url,permalink,timestamp,username,children{media_url,media_type,thumbnail_url}',
                'access_token' => $this->userAccessToken,
                'limit' => min($limit, 100),
            ],
        ]);

        if ($feedResponse->getStatusCode() !== 200) {
            throw new Exception('Failed to fetch Instagram media: ' . $feedResponse->getStatusCode());
        }
        $feed = json_decode($feedResponse->getBody()->getContents(), true);
        if (empty($feed['data'])) {
            $this->logger->info('InstagramProvider: No posts found in the media response.');
            throw new Exception('No posts found in Instagram media.');
        }

        $posts = [];
        foreach ($feed['data'] as $feedItem) {
            $post = new SocialMediaPostDto();
            $post->platform = self::PLATFORM;
            $post->externalId = $feedItem['id'] ?? '';
            $post->id = md5(self::PLATFORM . '_' . ($feedItem['id'] ?? ''));
            $post->content = $feedItem['caption'] ?? '';
            $post->publishedAt = new DateTime($feedItem['timestamp'] ?? 'now');
            $post->url = $feedItem['permalink'] ?? null;
            $post->hashtags = $this->getHashtagsFromContent($post->content);

            if (($feedItem['media_type'] ?? '') === 'IMAGE') {
                if (!empty($feedItem['media_url'])) {
                    $post->addMedia('image', $feedItem['media_url']);
                }
            } elseif (($feedItem['media_type'] ?? '') === 'VIDEO') {
                if (!empty($feedItem['media_url'])) {
                    $post->addMedia('video', $feedItem['media_url']);
                }
                if (isset($feedItem['thumbnail_url'])) {
                    $post->addMedia('image', $feedItem['thumbnail_url']);
                }
            } elseif (($feedItem['media_type'] ?? '') === 'CAROUSEL_ALBUM' && !empty($feedItem['children']['data'])) {
                foreach ($feedItem['children']['data'] as $child) {
                    if (($child['media_type'] ?? '') === 'IMAGE') {
                        if (!empty($child['media_url'])) {
                            $post->addMedia('image', $child['media_url']);
                        }
                    } elseif (($child['media_type'] ?? '') === 'VIDEO') {
                        if (!empty($child['media_url'])) {
                            $post->addMedia('video', $child['media_url']);
                        }
                        if (isset($child['thumbnail_url'])) {
                            $post->addMedia('image', $child['thumbnail_url']);
                        }
                    }
                }
            }

            $posts[] = $post;
        }

        return $posts;
    }

    /**
     * Download media files associated with the given post and set local file paths for them.
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
            $detected = $this->getExtensionFromUrl($mediaItem['url']);
            $extension = $detected ?: ($mediaItem['type'] === 'video' ? 'mp4' : 'jpg');
            $filename = $this->generateMediaFilename($post, $extension, $index);
            $targetPath = $targetDir . '/' . $filename;

            if ($this->downloadFile($mediaItem['url'], $targetPath)) {
                $mediaItem['local_path'] = $targetPath;
            }
        }
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
