Feature: Exercise WebApiContext Set Header
  In order to validate the set_header step
  As a context developer
  I need to be able to add headers in a scenario before sending a request

Scenario:
  Given I set header "foobar" with value "bazquux"
  When I send a GET request to "echo"
  Then the response should contain "headers"
  And the response should contain "foobar"
  And the response should contain "bazquux"
