<?php

namespace KJanczyk\PimcoreSOMLBundle\Provider;

use GuzzleHttp\Client;
use KJanczyk\PimcoreSOMLBundle\Provider\ProviderInterface;

abstract class AbstractProvider implements ProviderInterface
{
    protected Client $httpClient;

    public function __construct()
    {
        $this->httpClient = new Client([
            'verify' => false,
            'timeout' => 30,
        ]);
    }
}
