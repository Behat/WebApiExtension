Feature: Exercise WebApiContext Basic authentication
  In order to validate the authentication step
  As a context developer
  I need to be able to use authentication in a scenario

Scenario:
  When I send a GET request to "/404"
  Then the response code should be 404
