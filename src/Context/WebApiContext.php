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

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Psr\Http\Client\ClientExceptionInterface as PsrClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface as SymfonyClientExceptionInterface;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\RequestInterface;

/**
 * Provides web API description definitions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 * @author Keyclic team <techies@keyclic.com>
 */
class WebApiContext extends ApiClientContext implements ApiClientContextInterface
{
    /**
     * Adds Basic Authentication header to next request.
     *
     * @param string $username
     * @param string $password
     *
     * @Given /^I am basic authenticating as "([^"]*)" with "([^"]*)" password$/
     */
    public function iAmBasicAuthenticatingAs($username, $password): void
    {
        $authorization = base64_encode($username.':'.$password);

        $this->removeHeader('Authorization');
        $this->addHeader('Authorization', 'Basic '.$authorization);
    }

    /**
     * Sets a HTTP Header.
     *
     * @param string $name  header name
     * @param string $value header value
     *
     * @Given /^I set header "([^"]*)" with value "([^"]*)"$/
     */
    public function iSetHeaderWithValue($name, $value): void
    {
        $this->addHeader($name, $value);
    }

    /**
     * Sends HTTP request to specific relative URL.
     *
     * @param string $method request method
     * @param string $url relative url
     *
     * @throws PsrClientExceptionInterface
     * @throws SymfonyClientExceptionInterface
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)"$/
     */
    public function iSendARequest($method, $url): void
    {
        $url = $this->prepareUrl($url);

        $this->sendRequest($method, $url, $this->getHeaders());
    }

    /**
     * Sends HTTP request to specific URL with field values from Table.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param TableNode $values table of post values
     *
     * @throws PsrClientExceptionInterface
     * @throws SymfonyClientExceptionInterface
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with values:$/
     */
    public function iSendARequestWithValues($method, $url, TableNode $values): void
    {
        $url = $this->prepareUrl($url);

        $body = array_map(function ($value) {
            return $this->replacePlaceHolder($value);
        }, $values->getRowsHash());

        $body = json_encode($body);

        $this->sendRequest($method, $url, $this->getHeaders(), $body);
    }

    /**
     * Sends HTTP request to specific URL with raw body from PyString.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param PyStringNode $body request body
     *
     * @throws PsrClientExceptionInterface
     * @throws SymfonyClientExceptionInterface
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with body:$/
     */
    public function iSendARequestWithBody($method, $url, PyStringNode $body): void
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $this->sendRequest($method, $url, $this->getHeaders(), $body);
    }

    /**
     * Sends HTTP request to specific URL with form data from PyString.
     *
     * @param string $method request method
     * @param string $url relative url
     * @param PyStringNode $body request body
     *
     * @throws PsrClientExceptionInterface
     * @throws SymfonyClientExceptionInterface
     *
     * @When /^(?:I )?send a ([A-Z]+) request to "([^"]+)" with form data:$/
     */
    public function iSendARequestWithFormData($method, $url, PyStringNode $body): void
    {
        $url = $this->prepareUrl($url);
        $body = $this->replacePlaceHolder(trim($body));

        $fields = [];
        parse_str(implode('&', explode("\n", $body)), $fields);
        $body = http_build_query($fields, null, '&');

        $this->addHeader('Content-Type', 'application/x-www-form-urlencoded');

        $this->sendRequest($method, $url, $this->getHeaders(), $body);
    }

    /**
     * Checks that response has specific status code.
     *
     * @param string $code status code
     *
     * @Then /^(?:the )?response code should be (\d+)$/
     */
    public function theResponseCodeShouldBe($code): void
    {
        $expected = intval($code);
        $statusCode = intval($this->getResponse()->getStatusCode());

        Assert::assertSame($expected, $statusCode);
    }

    /**
     * Checks that response body contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should contain "([^"]*)"$/
     */
    public function theResponseShouldContain($text): void
    {
        $expectedRegexp = '/'.preg_quote($text).'/i';
        $bodyResponse = (string) $this->getResponse()->getBody();

        Assert::assertRegExp($expectedRegexp, $bodyResponse);
    }

    /**
     * Checks that response body doesn't contains specific text.
     *
     * @param string $text
     *
     * @Then /^(?:the )?response should not contain "([^"]*)"$/
     */
    public function theResponseShouldNotContain($text): void
    {
        $expectedRegexp = '/'.preg_quote($text).'/';
        $bodyResponse = (string) $this->getResponse()->getBody();

        Assert::assertNotRegExp($expectedRegexp, $bodyResponse);
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
    public function theResponseShouldContainJson(PyStringNode $jsonString): void
    {
        $rawJsonString = $this->replacePlaceHolder($jsonString->getRaw());

        $expected = json_decode($rawJsonString, true);
        $actual = json_decode((string) $this->getResponse()->getBody(), true);

        Assert::assertNotNull($expected, 'Can not convert expected to json:\n'.$rawJsonString);
        Assert::assertNotNull($actual, 'Can not convert body response to json:\n'.$this->getResponse()->getBody());

        Assert::assertGreaterThanOrEqual(count($expected), count($actual));

        foreach ($expected as $key => $needle) {
            Assert::assertArrayHasKey($key, $actual);
            Assert::assertEquals($expected[$key], $actual[$key]);
        }
    }

    /**
     * Check if the response header has a specific value.
     *
     * @param string $name
     * @param string $expected
     *
     * @Then /^the response "([^"]*)" header should be "([^"]*)"$/
     */
    public function theResponseHeaderShouldBe($name, $expected): void
    {
        $actual = $this->getResponse()->getHeaderLine($name);
        Assert::assertEquals($expected, $actual);
    }

    /**
     * Prints last response body.
     *
     * @Then print response
     */
    public function printResponse(): void
    {
        $request = '';
        if ($this->getRequest() instanceof RequestInterface) {
            $request = sprintf(
                '%s %s',
                $this->getRequest()->getMethod(),
                (string) $this->getRequest()->getUri()
            );
        }

        $response = sprintf(
            "%d:\n%s",
            $this->getResponse()->getStatusCode(),
            (string) $this->getResponse()->getContent(false)
        );

        echo sprintf('%s => %s', $request, $response);
    }
}
