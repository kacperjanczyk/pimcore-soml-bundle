<?php

namespace Muz\Pimcore\SoMLBundle\Provider;

use GuzzleHttp\Exception\GuzzleException;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Psr\Log\LoggerInterface;

class TwitterProvider extends AbstractProvider
{
    private string $apiKey;
    private string $apiSecret;
    private string $bearerToken;

    public function __construct(
        string $twitterApiKey,
        string $twitterApiSecret,
        string $twitterBearerToken,
        LoggerInterface $logger
    ) {
        parent::__construct($logger);
        $this->apiKey = $twitterApiKey;
        $this->apiSecret = $twitterApiSecret;
        $this->bearerToken = $twitterBearerToken;
    }

    public function getPlatform(): string
    {
        return SocialMediaPostDto::PLATFORM_TWITTER;
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->apiSecret) && !empty($this->bearerToken);
    }

    public function fetchPosts(int $limit): array
    {
        if (!$this->isConfigured()) {
            $this->logger->warning('Twitter API is not configured properly');
            return [];
        }

        try {
            // Uses Twitter API v2
            $response = $this->httpClient->get('https://api.twitter.com/2/tweets/search/recent', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->bearerToken,
                ],
                'query' => [
                    'query' => 'from:YourCompanyHandle -is:retweet', // Customize this query
                    'max_results' => min($limit, 100),
                    'tweet.fields' => 'created_at,public_metrics,entities',
                    'expansions' => 'author_id,attachments.media_keys',
                    'user.fields' => 'name,username,profile_image_url',
                    'media.fields' => 'url,preview_image_url,type',
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (!isset($data['data'])) {
                return [];
            }

            $posts = [];
            $users = [];
            $media = [];

            // Index users
            if (isset($data['includes']['users'])) {
                foreach ($data['includes']['users'] as $user) {
                    $users[$user['id']] = $user;
                }
            }

            // Index media
            if (isset($data['includes']['media'])) {
                foreach ($data['includes']['media'] as $mediaItem) {
                    $media[$mediaItem['media_key']] = $mediaItem;
                }
            }

            foreach ($data['data'] as $tweet) {
                $post = new SocialMediaPostDto();
                $post->platform = $this->getPlatform();
                $post->externalId = $tweet['id'];
                $post->id = md5($this->getPlatform() . '_' . $tweet['id']);
                $post->content = $tweet['text'];
                $post->publishedAt = new \DateTime($tweet['created_at']);
                $post->url = 'https://twitter.com/i/web/status/' . $tweet['id'];

                // Add metrics
                if (isset($tweet['public_metrics'])) {
                    $post->likeCount = $tweet['public_metrics']['like_count'] ?? 0;
                    $post->shareCount = $tweet['public_metrics']['retweet_count'] ?? 0;
                    $post->commentCount = $tweet['public_metrics']['reply_count'] ?? 0;
                }

                // Add hashtags
                if (isset($tweet['entities']['hashtags'])) {
                    foreach ($tweet['entities']['hashtags'] as $hashtag) {
                        $post->hashtags[] = $hashtag['tag'];
                    }
                }

                // Add mentions
                if (isset($tweet['entities']['mentions'])) {
                    foreach ($tweet['entities']['mentions'] as $mention) {
                        $post->mentions[] = $mention['username'];
                    }
                }

                // Add media
                if (isset($tweet['attachments']['media_keys'])) {
                    foreach ($tweet['attachments']['media_keys'] as $mediaKey) {
                        if (isset($media[$mediaKey])) {
                            $mediaItem = $media[$mediaKey];
                            $mediaUrl = $mediaItem['type'] === 'photo'
                                ? $mediaItem['url']
                                : ($mediaItem['preview_image_url'] ?? null);

                            if ($mediaUrl) {
                                $post->addMedia(
                                    $mediaItem['type'] === 'photo' ? 'image' : 'video',
                                    $mediaUrl
                                );
                            }
                        }
                    }
                }

                $posts[] = $post;
            }

            return $posts;
        } catch (GuzzleException $e) {
            $this->logger->error('Failed to fetch Twitter posts', [
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
