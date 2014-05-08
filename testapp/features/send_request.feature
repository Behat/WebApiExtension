Feature: Exercise WebApiContext method choice
  In order to validate the send request step
  As a context developer
  I need to be able to use any HTTP1/1 method in a scenario

  Scenario: HEAD should not return a body.
    When I send a HEAD request to "echo"
    Then the response should not contain "HEAD"

  Scenario Outline: Other HTTP methods should be echoed in the output.
    When I send a <method> request to "echo"
    Then the response should contain "<method>"
    And the response code should be 200

    Examples:
    | method  |
    | GET     |
    | POST    |
    | PUT     |
    | DELETE  |
    | OPTIONS |
    | PATCH   |

