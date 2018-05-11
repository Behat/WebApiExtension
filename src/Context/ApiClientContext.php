<?php

/*
 * This file is part of the Behat WebApiExtension.
 *
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 * (c) Keyclic team <techies@keyclic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\WebApiExtension\Context;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Provides methods without Features methods.
 */
abstract class ApiClientContext implements ApiClientContextInterface
{
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

    /**
     * @return ClientInterface
     */
    private function getClient()
    {
        if (null === $this->client) {
            throw new \RuntimeException('Client has not been set in WebApiContext.');
        }

        return $this->client;
    }

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header.
     *
     * @param string $name
     * @param string $value
     *
     * @return ApiClientContext
     */
    public function addHeader($name, $value)
    {
        if (isset($this->headers[$name])
        && true === is_array($this->headers[$name])) {
            array_push($this->headers[$name], $value);

            return $this;
        }

        if (isset($this->headers[$name])
        && false === is_array($this->headers[$name])) {
            $this->headers[$name] = [
                $this->headers[$name],
                $value,
            ];

            return $this;
        }

        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Removes a header identified by $name.
     *
     * @param string $name
     *
     * @return ApiClientContext
     */
    public function removeHeader($name)
    {
        if (array_key_exists($name, $this->headers)) {
            unset($this->headers[$name]);
        }

        return $this;
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key
     * @param string $value replace value
     *
     * @return ApiClientContext
     *
     * @deprecated
     */
    public function setPlaceHolder($key, $value)
    {
        return $this->addPlaceholder($key, $value);
    }

    /**
     * Add place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key
     * @param string $value replace value
     *
     * @return ApiClientContext
     */
    public function addPlaceholder($key, $value)
    {
        $this->placeHolders[$key] = $value;

        return $this;
    }

    /**
     * Removes a placeholder identified by $key.
     *
     * @param string $key token name
     *
     * @return ApiClientContext
     */
    public function removePlaceHolder($key)
    {
        if (array_key_exists($key, $this->placeHolders)) {
            unset($this->placeHolders[$key]);
        }

        return $this;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string      $method
     * @param string      $url
     * @param array       $headers
     * @param string|null $body
     *
     * @throws GuzzleException
     */
    protected function sendRequest($method, $url, array $headers = [], $body = null)
    {
        $this->request = new Request($method, $url, $headers, $body);

        try {
            $this->response = $this->getClient()->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    protected function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    protected function prepareUrl($url)
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }
}
