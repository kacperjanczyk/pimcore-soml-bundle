<?php

namespace KJanczyk\PimcoreSOMLBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class PimcoreSOMLBundle extends AbstractPimcoreBundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator($this->getPath() . '/config')
        );
        $loader->load('services.yaml');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function getNiceName(): string
    {
        return "Pimcore Social Media Library Bundle";
    }

    public function getDescription(): string
    {
        return "A bundle to manage and integrate social media content within Pimcore.";
    }
}
