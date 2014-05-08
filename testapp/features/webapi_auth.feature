Feature: Exercise WebApiContext Basic authentication
  In order to validate the authentication step
  As a context developer
  I need to be able to use authentication in a scenario

Scenario:
  Given I am authenticating as "user" with "pass" password
  When I send a GET request to "echo"
  Then the response should contain "headers"
  And the response should contain "authorization"
  # "dXNlcjpwYXNz" === base64_encode('user', 'pass')
  And the response should contain "Basic dXNlcjpwYXNz"
