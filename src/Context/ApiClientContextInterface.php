<?php

/*
 * This file is part of the Behat WebApiExtension.
 *
 * (c) Keyclic team <techies@keyclic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context;

use Behat\Behat\Context\Context;
use GuzzleHttp\ClientInterface;

/**
 * Guzzle Client-aware interface for contexts.
 *
 * @author Frédéric G. Marand <fgm@osinet.fr>
 *
 * @see WebApiAwareInitializer
 */
interface ApiClientContextInterface extends Context
{
    /**
     * Sets Guzzle Client instance.
     *
     * @param ClientInterface $client guzzle Client instance
     */
    public function setClient(ClientInterface $client);
}
