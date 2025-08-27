<?php

namespace Muz\Pimcore\SoMLBundle\Service;

use App\Service\TagService;
use Carbon\Carbon;
use Exception;
use Muz\Pimcore\SoMLBundle\DTO\SocialMediaPostDto;
use Muz\Pimcore\SoMLBundle\Interface\TagServiceInterface;
use Muz\Pimcore\SoMLBundle\Provider\ProviderInterface;
use Pimcore\Model\Asset;
use Pimcore\Model\DataObject\Folder;
use Pimcore\Model\DataObject\SocialMediaPost;
use Pimcore\Model\Element\DuplicateFullPathException;
use Psr\Log\LoggerInterface;

class SocialMediaService
{
    private array $providers;
    private string $mediaFolder;
    private int $postLimit;
    private LoggerInterface $logger;

    private TagService $tagService;

    /**
     * @param iterable $providers
     * @param string $mediaFolder
     * @param int $postLimit
     * @param LoggerInterface $logger
     */
    public function __construct(
        iterable $providers,
        string $mediaFolder,
        int $postLimit,
        LoggerInterface $logger,
        TagServiceInterface $tagService
    ) {
        $this->providers = [];
        foreach ($providers as $provider) {
            if ($provider instanceof ProviderInterface) {
                $this->providers[$provider::PLATFORM] = $provider;
            }
        }

        $this->mediaFolder = $mediaFolder;
        $this->postLimit = $postLimit;
        $this->logger = $logger;
        $this->tagService = $tagService;
    }

    /**
     * Import posts from all configured providers
     *
     * @param string|null $specificPlatform Limit import to a specific platform
     * @return int Number of posts imported
     * @throws DuplicateFullPathException
     */
    public function importPosts(?string $specificPlatform = null): int
    {
        $postsFolder = $this->getOrCreateSocialMediaFolder();
        $assetsFolder = $this->getOrCreateMediaAssetsFolder();
        $importCount = 0;

        foreach ($this->providers as $platform => $provider) {
            if ($specificPlatform && $platform !== $specificPlatform) {
                continue;
            }

            try {
                if (!$provider->isConfigured()) {
                    throw new Exception("Provider for $platform is not configured.");
                }

                $this->logger->info("Fetching posts from $platform");
                $posts = $provider->fetchPosts($this->postLimit);

                foreach ($posts as $post) {
                    if ($post->hasMedia()) {
                        $provider->downloadMedia($post, $this->mediaFolder);
                        $this->createAssetsForMedia($post, $assetsFolder);
                    }
                    $importedPost = $this->createOrUpdatePost($post, $postsFolder);
                    $this->processTags($importedPost);
                    $importCount++;
                }
            } catch (Exception $e) {
                $this->logger->error("Error importing from $platform: " . $e->getMessage());
            }
        }

        return $importCount;
    }

    /**
     * Create assets for post media files
     *
     * @param SocialMediaPostDto $post
     * @param Asset\Folder $assetsFolder
     */
    private function createAssetsForMedia(SocialMediaPostDto $post, Asset\Folder $assetsFolder): void
    {
        foreach ($post->media as &$mediaItem) {
            if (empty($mediaItem['local_path']) || !file_exists($mediaItem['local_path'])) {
                continue;
            }

            $filename = basename($mediaItem['local_path']);
            $assetPath = $assetsFolder->getFullPath() . '/' . $filename;

            // Check if asset already exists
            $existingAsset = Asset::getByPath($assetPath);
            if ($existingAsset) {
                // Update existing asset
                $mediaItem['asset_id'] = $existingAsset->getId();
                continue;
            }

            // Create new asset
            try {
                $asset = new Asset();
                $asset->setParent($assetsFolder);
                $asset->setFilename($filename);
                $asset->setData(file_get_contents($mediaItem['local_path']));
                $asset->save();

                $mediaItem['asset_id'] = $asset->getId();
            } catch (Exception $e) {
                $this->logger->error('Failed to create asset for media', [
                    'post_id' => $post->id,
                    'media_path' => $mediaItem['local_path'],
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Create or update a social media post object
     *
     * @param SocialMediaPostDto $postDto
     * @param Folder $postsFolder
     * @return SocialMediaPost
     * @throws Exception
     */
    private function createOrUpdatePost(SocialMediaPostDto $postDto, Folder $postsFolder): SocialMediaPost
    {
        $post = SocialMediaPost::getByExternalId($postDto->externalId, 1);

        if (!$post) {
            $post = new SocialMediaPost();
            $post->setKey($postDto->id);
            $post->setParent($postsFolder);
            $post->setPublished(true);
            $post->setExternalId($postDto->externalId);
        }

        $post->setPlatform($postDto->platform);
        $post->setContent($postDto->content);
        $post->setPublishedAt(Carbon::instance($postDto->publishedAt));
        $post->setUrl($postDto->url);
        $post->setLikeCount($postDto->likeCount);
        $post->setShareCount($postDto->shareCount);
        $post->setCommentCount($postDto->commentCount);
        $post->setHashtags(implode(',', $postDto->hashtags));
        $post->setMentions(implode(',', $postDto->mentions));

        // Zbuduj relacje do Assetów jako obiekty, nie ID
        $mediaAssets = [];
        foreach ($postDto->media as $mediaItem) {
            if (!empty($mediaItem['asset_id'])) {
                $asset = Asset::getById((int)$mediaItem['asset_id']);
                if ($asset instanceof Asset) {
                    $mediaAssets[] = $asset;
                }
            }
        }

        if (!empty($mediaAssets)) {
            $post->setMediaAssets($mediaAssets);
        } else {
            // jeżeli brak mediów, wyczyść relację
            $post->setMediaAssets([]);
        }

        $post->save();
        return $post;
    }

    /**
     * Get or create social media folder
     *
     * @return Folder
     * @throws DuplicateFullPathException
     */
    private function getOrCreateSocialMediaFolder(): Folder
    {
        $postsFolder = Folder::getByPath('/SocialMediaPosts');

        if (!$postsFolder) {
            $postsFolder = new Folder();
            $postsFolder->setKey('SocialMediaPosts');
            $postsFolder->setParentId(1); // Root folder
            $postsFolder->save();
        }

        return $postsFolder;
    }

    /**
     * Get or create assets folder
     *
     * @return Asset\Folder
     * @throws DuplicateFullPathException
     */
    private function getOrCreateMediaAssetsFolder(): Asset\Folder
    {
        $assetsFolder = Asset\Folder::getByPath('/SocialMedia');

        if (!$assetsFolder) {
            $assetsFolder = new Asset\Folder();
            $assetsFolder->setParentId(1); // Root of assets
            $assetsFolder->setFilename('SocialMedia');
            $assetsFolder->save();
        }

        return $assetsFolder;
    }

    private function processTags(SocialMediaPost $post): void
    {
        $socialMediaTag = $this->tagService->getOrCreateTag('Social Media');
        $this->tagService->assignTagToElement('object', $post->getId(), $socialMediaTag);

        if (!$post->getHashtags()) {
            return;
        }

        foreach (explode(',', $post->getHashtags()) as $hashtag) {
            $hashTagTag = $this->tagService->getOrCreateTag($hashtag, $socialMediaTag);
            $this->tagService->assignTagToElement('object', $post->getId(), $hashTagTag);
        }
    }
}
