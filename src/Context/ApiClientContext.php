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

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use Psr\Http\Client\ClientExceptionInterface as PsrHttpClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface as SymfonyHttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface as SymfonyResponseInterface;

/**
 * Provides methods without Features methods.
 *
 * @author Keyclic team <techies@keyclic.com>
 */
abstract class ApiClientContext implements ApiClientContextInterface
{
    /**
     * @var string
     */
    protected $baseUri;

    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var array
     */
    private $placeHolders = [];

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ResponseInterface
     */
    private $response;

    public function setBaseUri(string $baseUri): ApiClientContextInterface
    {
        $this->baseUri = $baseUri;

        return $this;
    }

    public function setClient(ClientInterface $client): ApiClientContextInterface
    {
        $this->client = $client;

        return $this;
    }

    protected function getHeaders(): array
    {
        return $this->headers;
    }

    protected function addHeader(string $name, string $value): ApiClientContextInterface
    {
        if (false === isset($this->headers[$name])) {
            $this->headers[$name] = $value;

            return $this;
        }

        if (true === is_array($this->headers[$name])) {
            array_push($this->headers[$name], $value);

            return $this;
        }

        if (false === is_array($this->headers[$name])) {
            $this->headers[$name] = [
                $this->headers[$name],
                $value,
            ];

            return $this;
        }

        return $this;
    }

    protected function removeHeader(string $name): ApiClientContextInterface
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Add place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     */
    protected function addPlaceholder(string $key, string $value): ApiClientContextInterface
    {
        $this->placeHolders[$key] = $value;

        return $this;
    }

    /**
     * Removes a placeholder identified by $key.
     */
    protected function removePlaceHolder(string $key): ApiClientContext
    {
        if (array_key_exists($key, $this->placeHolders)) {
            unset($this->placeHolders[$key]);
        }

        return $this;
    }

    protected function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    protected function getResponse()
    {
        return $this->response;
    }

    /**
     * @throws PsrHttpClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws SymfonyHttpClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function sendRequest(string $method, string $uri, array $headers = [], ?string $body = null)
    {
        $this->request = new Request($method, $this->baseUri.$uri, $headers, $body);

        try {
            $this->response = $this->getClient()->sendRequest($this->request);
        }
        // This exception is returned when status code is superior to 400
        // Temporary fix, waiting for httpclient 4.3.2
        // TODO Remove after update
        catch (SymfonyHttpClientExceptionInterface $e) {
            /** @var SymfonyResponseInterface $response */
            $response = $e->getResponse();

            if (null === $response) {
                throw $e;
            }

            $psr17Factory = new Psr17Factory();

            $psrResponse = $psr17Factory->createResponse($response->getStatusCode());

            foreach ($response->getHeaders(false) as $name => $values) {
                $this->response = $e->getResponse();
            }

            $this->response = $psrResponse->withBody($psr17Factory->createStream($response->getContent(false)));
        }
    }

    /**
     * Replaces placeholders in provided text.
     */
    protected function replacePlaceHolder(string $string): string
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     */
    protected function prepareUrl(string $url): string
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }

    protected function getClient(): ClientInterface
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set in WebApiContext.');
        }

        return $this->client;
    }
}
