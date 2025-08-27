<?php

namespace Muz\Pimcore\SoMLBundle\Interface;

use Pimcore\Model\Element\Tag;

interface TagServiceInterface
{
    /**
     * Get or create a tag
     *
     * @param string $name
     * @param Tag|null $parent
     * @return Tag
     */
    public function getOrCreateTag(string $name, ?Tag $parent = null): Tag;

    /**
     * Assign tag to an element
     *
     * @param string $type Element type (object, document, asset)
     * @param int $elementId
     * @param Tag $tag
     */
    public function assignTagToElement(string $type, int $elementId, Tag $tag): Tag;
}
