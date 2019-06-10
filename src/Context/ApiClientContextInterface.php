<?php

/*
 * This file is part of the Behat WebApiExtension.
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context;

use Behat\Behat\Context\Context;
use Psr\Http\Client\ClientInterface;

/**
 * Guzzle Client-aware interface for contexts.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 * @author Keyclic team <techies@keyclic.com>
 *
 * @see WebApiAwareInitializer
 */
interface ApiClientContextInterface extends Context
{
    /**
     * Sets HttpClient instance.
     */
    public function setClient(ClientInterface $client): self;

    /**
     * Sets base of uri string.
     */
    public function setBaseUri(string $uri): self;
}
