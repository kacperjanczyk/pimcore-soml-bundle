<?php

namespace KJanczyk\PimcoreSOMLBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;

class PimcoreSOMLBundle extends AbstractPimcoreBundle
{
    public function getNiceName(): string
    {
        return "Pimcore Social Media Library Bundle";
    }

    public function getDescription(): string
    {
        return "A bundle to manage and integrate social media content within Pimcore.";
    }

    public function getComposerPackageName(): string
    {
        return "kjanczyk/pimcore-soml-bundle";
    }
}
