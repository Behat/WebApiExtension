<?php

namespace Behat\WebApiExtension\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use PHPUnit_Framework_Assert as Assertions;

/**
 * Provides web API description definitions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class WebApiContext implements WebApiAwareContext
{

    /**
     * @var string
     */
    private $authorization;

    /**
     * @var \GuzzleHttp\Client
     */
    private $client;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface
     */
    private $request;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface
     */
    private $response;

    private $browser;
    private $placeHolders = array();

    /**
     * @param \GuzzleHttp\Client $client
     */
    public function setClient(Client $client)
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
        $this->removeHeader('Authorization');
        $this->authorization = base64_encode($username . ':' . $password);
        $this->addHeader('Authorization: Basic ' . $this->authorization);
    }

    /**
     * Sets a HTTP Header.
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value)
    {
        $this->addHeader($name . ':' . $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url    relative url
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url)
    {
        $url = $this->prepareUrl($url);
        $this->request = $this->client->createRequest($method, $url);
        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }
        $this->response = $this->client->send($this->request);
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string    $method request method
     * @param string    $url    relative url
     * @param TableNode $post   table of post values
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $post)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($post->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $bodyOption = array(
          'body' => json_encode($fields),
        );
        $this->request = $this->client->createRequest($method, $url, $bodyOption);
        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }

        $this->response = $this->client->send($this->request);
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $string request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $string)
    {
        $url = $this->prepareUrl($url);
        $string = $this->replacePlaceHolder(trim($string));

        $this->request = $this->client->createRequest(
                                      $method,
                                        $url,
                                        array(
                                          'headers' => $this->getHeaders(),
                                          'body' => $string,
                                        )
        );
        $this->response = $this->client->send($this->request);

    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string       $method request method
     * @param string       $url    relative url
     * @param PyStringNode $body   request body
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body)
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = array();
        parse_str(implode('&', explode("\n", $body)), $fields);
        $this->request = $this->client->createRequest($method, $url);
        /** @var \GuzzleHttp\Post\PostBodyInterface $requestBody */
        $requestBody = $this->request->getBody();
        foreach ($fields as $key => $value) {
            $requestBody->setField($key, $value);
        }

        $this->response = $this->client->send($this->request);
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code)
    {
        $expected = intval($code);
        $actual = intval($this->response->getStatusCode());
        Assertions::assertSame($expected, $actual);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/i';
        $actual = (string) $this->response->getBody();
        Assertions::assertRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text)
    {
        $expectedRegexp = '/' . preg_quote($text) . '/';
        $actual = (string) $this->response->getBody();
        Assertions::assertNotRegExp($expectedRegexp, $actual);
    }

    /**
     * Checks that response body contains JSON from PyString.
     *
     * Do not check that the response body /only/ contains the JSON from PyString,
     *
     * @param PyStringNode $jsonString
     *
     * @throws \RuntimeException
     *
     * @Then /^(?:the )?response should contain json:$/
     *
     */
    public function theResponseShouldContainJson(PyStringNode $jsonString)
    {
        $etalon = json_decode($this->replacePlaceHolder($jsonString->getRaw()), true);
        $actual = $this->response->json();

        if (null === $etalon) {
            throw new \RuntimeException(
              "Can not convert etalon to json:\n" . $this->replacePlaceHolder($jsonString->getRaw())
            );
        }

        Assertions::assertGreaterThanOrEqual(count($etalon), count($actual));
        foreach ($etalon as $key => $needle) {
            Assertions::assertArrayHasKey($key, $actual);
            Assertions::assertEquals($etalon[$key], $actual[$key]);
        }
    }

    /**
     * Prints beautified debug string.
     *
     * @param string $string debug string
     */
    public function printDebug($string)
    {
        echo "\n\033[36m|  " . strtr($string, array("\n" => "\n|  ")) . "\033[0m\n\n";
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse()
    {
        $request = $this->request;
        $response = $this->response;

        $this->printDebug(
             sprintf(
               "%s %s => %d:\n%s",
               $request->getMethod(),
               $request->getUrl(),
               $response->getStatusCode(),
               $response->getBody()
             )
        );
    }

    /**
     * Prepare URL by replacing placeholders and trimming slashes.
     *
     * @param string $url
     *
     * @return string
     */
    private function prepareUrl($url)
    {
        return ltrim($this->replacePlaceHolder($url), '/');
    }

    /**
     * Sets place holder for replacement.
     *
     * you can specify placeholders, which will
     * be replaced in URL, request or response body.
     *
     * @param string $key   token name
     * @param string $value replace value
     */
    public function setPlaceHolder($key, $value)
    {
        $this->placeHolders[$key] = $value;
    }

    /**
     * Replaces placeholders in provided text.
     *
     * @param string $string
     *
     * @return string
     */
    public function replacePlaceHolder($string)
    {
        foreach ($this->placeHolders as $key => $val) {
            $string = str_replace($key, $val, $string);
        }

        return $string;
    }

    /**
     * Returns headers, that will be used to send requests.
     *
     * @return array
     */
    protected function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Adds header
     *
     * @param string $header
     */
    protected function addHeader($header)
    {
        $this->headers[] = $header;
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        foreach ($this->headers as $headerIndex => $headerValue) {
            if (strpos($headerValue, $headerName) === 0) {
                unset($this->headers[$headerIndex]);
            }
        }
    }
}
