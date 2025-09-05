<?php

namespace KJanczyk\PimcoreSoMLBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcoreSoMLBundle extends AbstractPimcoreBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getNiceName(): string
    {
        return 'Social Media Library Bundle';
    }

    public function getDescription(): string
    {
        return 'Manages social media posts from multiple platforms';
    }

    public function getJsPaths(): array
    {
        return ['/bundles/pimcoresoml/js/extension.js'];
    }

    public function getAdminJsPaths(): array
    {
        return ['/bundles/pimcoresoml/js/extension.js'];
    }
}
