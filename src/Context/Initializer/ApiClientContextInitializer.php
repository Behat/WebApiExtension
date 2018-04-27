<?php

/*
 * This file is part of the Keyclic WebApiExtension.
 *
 * (c) Keyclic team <techies@keyclic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Behat\WebApiExtension\Context\ApiClientContextInterface;
use GuzzleHttp\ClientInterface;

/**
 * Guzzle-aware contexts initializer.
 *
 * Sets Guzzle client instance to the ApiClientAwareContext.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 */
class ApiClientContextInitializer implements ContextInitializer
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * Initializes initializer.
     *
     * @param ClientInterface $client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Initializes provided context.
     *
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof ApiClientContextInterface === true) {
            /** @var $context ApiClientContextInterface */
            $context->setClient($this->client);
        }
    }
}
