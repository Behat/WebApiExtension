<?php

/*
 * This file is part of the Behat WebApiExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\WebApiExtension\Context\WebApiAwareContext;
use GuzzleHttp\Client;

/**
 * Guzzle-aware contexts initializer.
 *
 * Sets Guzzle client instance to the WebApiAware contexts.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 */
class WebApiAwareInitializer implements ContextInitializer
{
    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * Initializes initializer.
     *
     * @param \GuzzleHttp\Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Initializes provided context.
     *
     * @param \Behat\Behat\Context\Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof WebApiAwareContext) {
            $context->setClient($this->client);
        }
    }
}
