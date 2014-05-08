Feature: Exercise WebApiContext data sending
  In order to validate the send request step
  As a context developer
  I need to be able to send a request with values in a scenario

  Scenario:
    When I send a POST request to "echo" with values:
    | name | name |
    | pass | pass |
    Then the response should contain "POST"
    And the response should contain json:
    """
    {
    "name" : "name",
    "pass": "pass"
    }
    """
