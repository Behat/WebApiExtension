<?php

/*
 * This file is part of the Behat WebApiExtension.
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\WebApiExtension\Context\ApiClientContextInterface;
use Psr\Http\Client\ClientInterface;

/**
 * HttpClient contexts initializer.
 *
 * Sets http client instance to the ApiClientAwareContext.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 * @author Keyclic <techies@keyclic.com>
 */
class ApiClientContextInitializer implements ContextInitializer
{
    /**
     * @var string
     */
    private $baseUri;

    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client, array $config)
    {
        $this->client = $client;
        $this->baseUri = $config['base_uri'];
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if (true === $context instanceof ApiClientContextInterface) {
            /* @var $context ApiClientContextInterface */
            $context->setClient($this->client);
            $context->setBaseUri($this->baseUri);
        }
    }
}
