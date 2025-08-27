<?php

namespace Muz\Pimcore\SoMLBundle\DTO;

class SocialMediaPostDto
{
    public const PLATFORM_TWITTER = 'twitter';
    public const PLATFORM_INSTAGRAM = 'instagram';
    public const PLATFORM_FACEBOOK = 'facebook';
    public const PLATFORM_LINKEDIN = 'linkedin';

    public string $id;
    public string $platform;
    public string $externalId;
    public string $content;
    public \DateTime $publishedAt;
    public ?string $url = null;
    public array $media = [];
    public int $likeCount = 0;
    public int $shareCount = 0;
    public int $commentCount = 0;
    public array $hashtags = [];
    public array $mentions = [];

    /**
     * @param string $mediaType One of "image", "video"
     * @param string $url The URL of the media
     * @param string $localPath Optional local path if media has been downloaded
     */
    public function addMedia(string $mediaType, string $url, ?string $localPath = null): void
    {
        $this->media[] = [
            'type' => $mediaType,
            'url' => $url,
            'local_path' => $localPath,
        ];
    }

    public function hasMedia(): bool
    {
        return !empty($this->media);
    }

    public function getFirstMediaUrl(): ?string
    {
        if (empty($this->media)) {
            return null;
        }

        return $this->media[0]['url'] ?? null;
    }

    public function getFirstMediaLocalPath(): ?string
    {
        if (empty($this->media)) {
            return null;
        }

        return $this->media[0]['local_path'] ?? null;
    }
}
