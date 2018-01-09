Feature: Refused Transactions
  As a magento store manager
  I would like to see orders that was refused on Pagar.me
  So I can could revert this situation

  Scenario:
    Given an admin user
    When I access the admin
    And I go to Pagar.me refused transactions page
    Then a refused transactions list should be visible
