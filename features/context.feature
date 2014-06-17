Feature: client aware context
  In order to write steps using the API
  As a step definitions developer
  I need get the API client in my feature context

  Background:
    Given a file named "features/bootstrap/FeatureContext.php" with:
      """
      <?php

      use Behat\WebApiExtension\Context\ApiClientAwareContext;
      use GuzzleHttp\Client;

      class FeatureContext implements ApiClientAwareContext
      {
          private $client;

          public function setClient(Client $client)
          {
              $this->client = $client;
          }

          /**
           * @Then /^the client should be set$/
           */
          public function theClientShouldBeSet() {
              PHPUnit_Framework_Assert::assertInstanceOf('GuzzleHttp\Client', $this->client);
          }

          /**
           * @Then the client default option :option should be equal to true
           */
          public function theClientDefaultOptionShouldBeEqualToTrue($option) {
              PHPUnit_Framework_Assert::assertSame(true, $this->client->getDefaultOption($option));
          }
      }
      """

  Scenario: Context parameters
    Given a file named "behat.yml" with:
      """
      default:
        extensions:
          Behat\WebApiExtension:
            defaults:
              debug: true
      """
    And a file named "features/client.feature" with:
      """
      Feature: Api client
        In order to call the API
        As feature runner
        I need to be able to access the client

        Scenario: client is set
          Then the client should be set
          And the client default option "debug" should be equal to true
      """
    When I run "behat -f progress features/client.feature"
    Then it should pass with:
      """
      ..

      1 scenario (1 passed)
      2 steps (2 passed)
      """
