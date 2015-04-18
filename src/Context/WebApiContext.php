<?php

/*
 * This file is part of the Behat WebApiExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Behat\WebApiExtension\Context;

use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Post\PostBody;
use GuzzleHttp\Message\RequestInterface;
use GuzzleHttp\Message\ResponseInterface;
use PHPUnit_Framework_Assert as Assertions;
use Symfony\Component\HttpFoundation\HeaderBag;

/**
 * Provides web API description definitions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Łukasz Czarnołęcki <lukasz@czarnolecki.pl>
 */
class WebApiContext extends RouterContext implements ApiClientAwareContext
{
    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var HeaderBag
     */
    protected $headers;

    /**
     * {@inheritdoc}
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^I am authenticating as "([^"]*)" with "([^"]*)" password$/
     */
    public function iAmAuthenticatingAs($username, $password)
    {
        $this->getHeadersBag()->remove('Authorization');
        $authorization = base64_encode($username . ':' . $password);
        $this->getHeadersBag()->set('Authorization', 'Basic ' . $authorization);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method
     * @param string $route
     * @param TableNode $table
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $route, TableNode $table = null)
    {
        $url = $this->getRoute($route);
        $request = $this->client->createRequest($method, $url, ['headers' => $this->getHeadersBag()->all()]);
        if (null !== $table) {
            $body = $request->getBody();
            /** @var $body PostBody */
            $body->replaceFields($table->getRowsHash());
        }
        $this->send($request);
    }

    /**
     * Checks that the response has specific status code.
     *
     * @Then /^the response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        Assertions::assertSame(intval($this->response->getStatusCode()), intval($code));
    }

    /**
     * Checks that the response data is the same as passed.
     *
     * @Then the response should be equal:
     */
    public function theResponseShouldBeEqual(TableNode $table)
    {
        $expected = $table->getRowsHash();
        $response = $this->response->json();
        Assertions::assertCount(count($expected), $response);
        foreach ($expected as $key => $value) {
            Assertions::assertArrayHasKey($key, $response);
            Assertions::assertEquals($value, $expected[$key]);
        }
    }

    /**
     * Checks that the response field is the same as passed.
     *
     * @Then the response field ([[\w]*[\d]*]+) should be equal:
     */
    public function theResponseFieldShouldBeEqual($field, TableNode $table)
    {
        $expected = $table->getRowsHash();
        $response = $this->response->json();

        Assertions::assertArrayHasKey($field, $response);
        $fieldValues = $response[$field];

        Assertions::assertCount(count($expected), $fieldValues);
        foreach ($expected as $key => $value) {
            Assertions::assertArrayHasKey($key, $fieldValues);
            Assertions::assertEquals($value, $fieldValues[$key]);
        }
    }

    /**
     * @return HeaderBag
     */
    protected function getHeadersBag()
    {
        if (null === $this->headers) {
            $this->initializeHeaders();
        }
        return $this->headers;
    }

    private function initializeHeaders()
    {
        $this->headers = new HeaderBag(["Accept" => "application/json"]);
    }

    /**
     * Sends a request with error handling
     *
     * @param RequestInterface $request
     */
    private function send(RequestInterface $request)
    {
        try {
            $this->response = $this->client->send($request);
        } catch (ClientException $e) {
            $this->response = $e->getResponse();
            if (null === $this->response) {
                throw $e;
            }
        }
    }
}
