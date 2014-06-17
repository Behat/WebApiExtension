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
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\History;
use PHPUnit_Framework_Assert as Assertions;

/**
 * Provides web API description definitions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class WebApiContext implements ApiClientAwareContext
{
    /**
     * @var string
     */
    private $authorization;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var array
     */
    private $headers = array();

    /**
     * @var array
     */
    private $requestOptions = array();

    /**
     * @var \GuzzleHttp\Message\RequestInterface
     */
    private $request;

    /**
     * @var History
     */
    private $requestHistory;

    /**
     * @var \GuzzleHttp\Message\ResponseInterface
     */
    private $response;

    private $placeHolders = array();

    /**
     * {@inheritdoc}
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
        $this->addHeader('Authorization', 'Basic ' . $this->authorization);
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
        $this->addHeader($name, $value);
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
        $this->createRequest($method, $url);

        $this->sendRequest();
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
        $this->createRequest($method, $url, $bodyOption);

        $this->sendRequest();
    }

    /**
     * @When (I) send a :method request to :url with query parameters:
     */
    public function iSendARequestWithQueryParameters($method, $url, TableNode $parameters)
    {
        $url = $this->prepareUrl($url);
        $fields = array();

        foreach ($parameters->getRowsHash() as $key => $val) {
            $fields[$key] = $this->replacePlaceHolder($val);
        }

        $this->createRequest($method, $url);
        $this->request->setQuery($fields);

        $this->sendRequest();
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

        $this->createRequest(
            $method,
            $url,
            array(
                'headers' => $this->getHeaders(),
                'body' => $string,
            )
        );
        $this->sendRequest();
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
        $this->createRequest($method, $url);
        /** @var \GuzzleHttp\Post\PostBodyInterface $requestBody */
        $requestBody = $this->request->getBody();
        foreach ($fields as $key => $value) {
            $requestBody->setField($key, $value);
        }

        $this->sendRequest();
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
     * @Then (the) response header :header should be equal to :value
     */
    public function theResponseHeaderShouldBeEqualTo($header, $value)
    {
        Assertions::assertSame($this->response->getHeader($header), $value);
    }

    /**
     * @Then (the) response header :header should not be equal to :value
     */
    public function theResponseHeaderShouldNotBeEqualTo($header, $value)
    {
        Assertions::assertNotSame($this->response->getHeader($header), $value);
    }

    /**
     * @Then (the) response header :header should contain :value
     */
    public function theResponseHeaderShouldContain($header, $value)
    {
        Assertions::assertContains($value, $this->response->getHeader($header));
    }

    /**
     * @Then (the) response header :header should not contain :value
     */
    public function theResponseHeaderShouldNotContain($header, $value)
    {
        Assertions::assertNotContains($value, $this->response->getHeader($header));
    }

    /**
     * @Then (the) response should be equal to:
     */
    public function theResponseShouldBeEqualTo(PyStringNode $text)
    {
        $actual = (string) $this->response->getBody();
        Assertions::assertSame($text->getRaw(), $actual);
    }

    /**
     * @Then (the) response should not be equal to:
     */
    public function theResponseShouldNotBeEqualTo(PyStringNode $text)
    {
        $actual = (string) $this->response->getBody();
        Assertions::assertNotSame($text->getRaw(), $actual);
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
     * @Given (I) do not follow redirects
     */
    public function iDoNotFollowRedirects()
    {
        $this->requestOptions['allow_redirects'] = false;
    }

    /**
     * @Then (I) should be redirected
     */
    public function iShouldBeRedirected()
    {
        $firstResponse = $this->getHistoryFirstResponse();
        $status = $firstResponse->getStatusCode();
        $isFirstResponseRedirect = $status >= 300 && $status < 400;
        Assertions::assertTrue($isFirstResponseRedirect, sprintf('Response of status %d is not a redirect', $status));
        Assertions::assertArrayHasKey('Location', $firstResponse->getHeaders());
    }

    /**
     * @Then (I) should be redirected to :to
     */
    public function iShouldBeRedirectedTo($to)
    {
        $this->iShouldBeRedirected();

        $firstResponse = $this->getHistoryFirstResponse();
        Assertions::assertSame($to, $firstResponse->getHeader('Location'));
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

        echo sprintf(
            "%s %s => %d:\n%s",
            $request->getMethod(),
            $request->getUrl(),
            $response->getStatusCode(),
            $response->getBody()
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
    protected function replacePlaceHolder($string)
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
     * @param string $name
     * @param string $value
     */
    protected function addHeader($name, $value)
    {
        if (isset($this->headers[$name])) {
            if (!is_array($this->headers[$name])) {
                $this->headers[$name] = array($this->headers[$name]);
            }

            $this->headers[$name][] = $value;
        } else {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes a header identified by $headerName
     *
     * @param string $headerName
     */
    protected function removeHeader($headerName)
    {
        if (array_key_exists($headerName, $this->headers)) {
            unset($this->headers[$headerName]);
        }
    }

    private function sendRequest()
    {
        try {
            $this->response = $this->client->send($this->request);
        } catch (RequestException $e) {
            $this->response = $e->getResponse();

            if (null === $this->response) {
                throw $e;
            }
        }
    }

    private function createRequest($method, $url, $options = array())
    {
        if (!empty($this->requestOptions)) {
            $options = array_merge($this->requestOptions, $options);
            $this->requestOptions = array();
        }

        $this->request = $this->client->createRequest($method, $url, $options);
        $this->request->getEmitter()->attach($this->requestHistory = new History());

        if (!empty($this->headers)) {
            $this->request->addHeaders($this->headers);
        }
    }

    private function getHistoryFirstResponse()
    {
        $historyIterator = $this->requestHistory->getIterator();
        $historyIterator->rewind();
        $firstTransaction = $historyIterator->current();
        $firstResponse = isset($firstTransaction['response']) ? $firstTransaction['response'] : null;

        if (null === $firstResponse) {
            throw new \RuntimeException('No response found in the last request transaction log.');
        }

        return $firstResponse;
    }
}
