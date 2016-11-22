<?php

/*
 * This file is part of the Behat WebApiExtension.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Behat\WebApiExtension\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use PHPUnit_Framework_Assert as Assertions;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

/**
 * Provides web API description definitions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Łukasz Czarnołęcki <lukasz@czarnolecki.pl>
 */
class WebApiContext extends RouterContext implements ApiClientAwareContextInterface
{
    /**
     * @var string
     */
    protected $jsonResponse;

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
     * @var string
     */
    protected $responseContent;

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
     * Set value to specific header
     *
     * @Given /^Header "([^"]*)" has value "([^"]+)"$/
     */
    public function iSetHeaderWithValue($header, $value)
    {
        $this->getHeadersBag()->set($header, $value);
    }

    /**
     * Sends HTTP request to specific route.
     *
     * @param string $method
     * @param string $route
     * @param TableNode $table
     *
     * @When /^(?:I )?send a (GET|POST) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $route, TableNode $table = null)
    {
        $url     = $this->getUrl($route);
        $request = new GuzzleRequest($method, $url, $this->getHeadersBag()->all(), $table->getRowsHash());
        $this->send($request);
    }

    /**
     * Sends HTTP POST request to specific route with body wrapped by field.
     *
     * @param string $route
     * @param string $field
     * @param TableNode $table
     *
     * @When /^(?:I )?send a POST request to "([^"]+)" with field (\w+)$/
     */
    public function iSendAPostRequestWithField($route, $field, TableNode $table = null)
    {
        $url     = $this->getUrl($route);
        $request = new GuzzleRequest(Request::METHOD_POST, $url, $this->getHeadersBag()->all(), null !== $table ? [$field => $table->getRowsHash()] : null);
        $this->send($request);
    }

    /**
     * Sends HTTP request to specific path.
     *
     * @param string $method
     * @param string $path
     * @param TableNode $table
     *
     * @When /^(?:I )?send a (GET|PUT|DELETE|PATCH|POST) request to path "([^"]+)"$/
     */
    public function iSendARequestToPath($method, $path, TableNode $table = null)
    {
        $url     = $this->getUrlFromPath($path, $method);
        $request = new GuzzleRequest($method, $url, $this->getHeadersBag()->all(), null !== $table ? json_encode($table->getRowsHash()) : null);
        $this->send($request);
    }

    /**
     * @When /^(?:I )?send a (PUT|PATCH|POST) request to path "([^"]+)" with payload$/
     */
    public function iSendARequestToPathWithPayload($method, $path, PyStringNode $payload)
    {
        $url     = $this->getUrlFromPath($path, $method);
        $request = new GuzzleRequest($method, $url, $this->getHeadersBag()->all(), $payload->getRaw());
        $this->send($request);
    }

    /**
     * Sends HTTP request to specific path with body wrapped by field.
     *
     * @param string $method
     * @param string $path
     * @param string $field
     * @param TableNode $table
     *
     * @When /^(?:I )?send a (PUT|PATCH|POST) request to path "([^"]+)" with field (\w+)$/
     */
    public function iSendARequestWithField($method, $path, $field, TableNode $table = null)
    {
        $url     = $this->getUrlFromPath($path, $method);
        $request = new GuzzleRequest($method, $url, $this->getHeadersBag()->all(), null !== $table ? json_encode([$field => $table->getRowsHash()]) : null);
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
     * Checks that the response has specific header with value
     *
     * @Then /^the response header ([\w\|\-]+) should be "([^"]+)"$/
     */
    public function theResponseHeaderShouldBe($header, $value)
    {
        Assertions::assertTrue($this->response->hasHeader($header));
        Assertions::assertEquals($value, $this->response->getHeader($header)[0]);
    }

    /**
     * Checks that the response has header location contains path
     *
     * @Then /^the response header location should contains "([^"]+)"$/
     */
    public function theResponseHeaderLocationShouldContainsPath($path)
    {
        Assertions::assertTrue($this->response->hasHeader('location'));
        Assertions::assertContains($path, $this->response->getHeader('location')[0]);
    }

    /**
     * Checks that the response data is the same as passed.
     *
     * @Then the response should be equal:
     */
    public function theResponseShouldBeEqual(TableNode $table)
    {
        $expected = $table->getRowsHash();
        $response = $this->jsonResponse;
        Assertions::assertCount(count($expected), $response);
        foreach ($expected as $key => $value) {
            Assertions::assertArrayHasKey($key, $response);
            Assertions::assertEquals($value, $expected[$key]);
        }
    }

    /**
     * Checks that the response field is the same as passed.
     *
     * @Then /^the response field ([\w]+) should be equal:$/
     */
    public function theResponseFieldShouldBeEqual($field, TableNode $table)
    {
        $expected = $table->getHash();
        $response = $this->jsonResponse;

        Assertions::assertArrayHasKey($field, $response);
        $fieldValues = $response[$field];

        Assertions::assertCount(count($expected), $fieldValues);
        foreach ($expected as $key => $value) {
            Assertions::assertArrayHasKey($key, $fieldValues);
            Assertions::assertEquals($value, $fieldValues[$key]);
        }
    }

    /**
     * @Then /^the response should be equal json:$/
     */
    public function theResponseShouldBeEqualJson(PyStringNode $jsonString)
    {
        $actual   = $this->jsonResponse;
        $expected = json_decode($jsonString, true);

        Assertions::assertEquals($expected, $actual);
    }

    /**
     * @Then /^the response should contains "([\w]+)"$/
     */
    public function theResponseShouldContain($text)
    {
        $actual   = $this->jsonResponse;

        Assertions::assertContains($text, $actual);
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
        $this->headers = new HeaderBag(["Accept" => "application/json", 'Content-Type' => 'application/json']);
    }

    /**
     * Sends a request with error handling
     *
     * @param RequestInterface $request
     */
    protected function send(RequestInterface $request)
    {
        try {
            $this->response = $this->client->send($request);
            if((string)$this->response->getBody()) {
                $this->jsonResponse = json_decode((string) $this->response->getBody(), true);
            }
        } catch (ClientException $e) {
            $this->response = $e->getResponse();
            //when 404 or 401 or 403 or something else
            $this->jsonResponse = $this->response->getBody()->getContents();
            if (null === $this->response) {
                throw $e;
            }
        }
    }
}
