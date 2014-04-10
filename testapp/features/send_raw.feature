Feature: Exercise WebApiContext data sending
  In order to validate the send request step
  As a context developer
  I need to be able to send a request with values in a scenario

  Scenario:
    Given I set header "content-type" with value "application/json"
    When I send a POST request to "echo" with body:
    """
    {
    "name" : "name",
    "pass": "pass"
    }
    """
    Then print response
    Then the response should contain "POST"
    And the response should contain json:
    """
    {
    "name" : "name",
    "pass": "pass"
    }
    """
