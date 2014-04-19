Feature: Extension test
  In order to test the extension easily
  As a WebApi feature tester
  I want to be able to find features automatically

  Scenario: Features should be loaded from the test application
    When I run "behat"
    Then it should pass with:
      """
      14 scenarios (14 passed)
      """
