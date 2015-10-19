Feature: Feature failures
  In order to resolve problems easily
  As a WebApi feature tester
  I want clear error messages

  Background:
    Given a file named "behat.yml" with:
      """
      default:
          formatters:
              progress: ~
          extensions:
              Behat\WebApiExtension:
                  base_url: http://localhost:8080/

          suites:
              default:
                  contexts: ['Behat\WebApiExtension\Context\WebApiContext']
      """

  Scenario: Response code
    Given a file named "features/authentication.feature" with:
      """
      Feature: Accessing an invalid url
        In order to known about my mistakes
        As an API client
        I should receive an error response

      Scenario:
        When I send a GET request to "/404"
        Then the response code should be 200
      """
    When I run "behat features/authentication.feature"
    Then it should fail with:
      """
      .F

      --- Failed steps:

          Then the response code should be 200 # features/authentication.feature:8
            The response code was 404, not 200 (Behat\WebApiExtension\Context\ExpectationException)

      1 scenario (1 failed)
      2 steps (1 passed, 1 failed)
      """

  Scenario: Response contains
    Given a file named "features/headers.feature" with:
      """
      Feature: Exercise WebApiContext Set Header
        In order to validate the set_header step
        As a context developer
        I need to be able to add headers in a scenario before sending a request

      Scenario:
        When I send a GET request to "echo"
        Then the response should contain "foo"
      """
    When I run "behat features/headers.feature"
    Then it should fail with:
      """
      .F

      --- Failed steps:

          Then the response should contain "foo" # features/headers.feature:8
            Response body does not contain the specified text (Behat\WebApiExtension\Context\ExpectationException)

      1 scenario (1 failed)
      2 steps (1 passed, 1 failed)
      """

  Scenario: Response does not contain
    Given a file named "features/headers.feature" with:
      """
      Feature: Exercise WebApiContext Set Header
        In order to validate the set_header step
        As a context developer
        I need to be able to add headers in a scenario before sending a request

      Scenario:
        Given I set header "foo" with value "bar"
        When I send a GET request to "echo"
        Then the response should not contain "foo"
      """
    When I run "behat features/headers.feature"
    Then it should fail with:
      """
      ..F

      --- Failed steps:

          Then the response should not contain "foo" # features/headers.feature:9
            Response body contains the specified text (Behat\WebApiExtension\Context\ExpectationException)

      1 scenario (1 failed)
      3 steps (2 passed, 1 failed)
      """

  Scenario: Response contains JSON (invalid JSON)
    Given a file named "features/headers.feature" with:
      """
      Feature: Exercise WebApiContext Set Header
        In order to validate the set_header step
        As a context developer
        I need to be able to add headers in a scenario before sending a request

      Scenario:
        When I send a GET request to "echo"
        And the response should contain json:
        '''
        foo
        '''
      """
    When I run "behat features/headers.feature"
    Then it should fail with:
      """
      .F

      --- Failed steps:

          And the response should contain json: # features/headers.feature:8
            Can not convert etalon to json:
            foo (LogicException)

      1 scenario (1 failed)
      2 steps (1 passed, 1 failed)
      """

  Scenario: Response contains JSON (missing key)
    Given a file named "features/headers.feature" with:
      """
      Feature: Exercise WebApiContext Set Header
        In order to validate the set_header step
        As a context developer
        I need to be able to add headers in a scenario before sending a request

      Scenario:
        When I send a GET request to "echo"
        And the response should contain json:
        '''
        {
        "foo" : "bar"
        }
        '''
      """
    When I run "behat features/headers.feature"
    Then it should fail with:
      """
      .F

      --- Failed steps:

          And the response should contain json: # features/headers.feature:8
            Does not contain the key "foo" (Behat\WebApiExtension\Context\ExpectationException)

      1 scenario (1 failed)
      2 steps (1 passed, 1 failed)
      """

  Scenario: Response contains JSON
    Given a file named "features/headers.feature" with:
      """
      Feature: Exercise WebApiContext Set Header
        In order to validate the set_header step
        As a context developer
        I need to be able to add headers in a scenario before sending a request

      Scenario:
        When I send a POST request to "echo" with form data:
        '''
        foo=bar
        '''
        And the response should contain json:
        '''
        {
        "foo" : "baz"
        }
        '''
      """
    When I run "behat features/headers.feature"
    Then it should fail with:
      """
      .F

      --- Failed steps:

          And the response should contain json: # features/headers.feature:11
            Value for the key "foo" does not match (Behat\WebApiExtension\Context\ExpectationException)

      1 scenario (1 failed)
      2 steps (1 passed, 1 failed)
      """
