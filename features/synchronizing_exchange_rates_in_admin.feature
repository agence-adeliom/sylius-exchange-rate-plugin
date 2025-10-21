@admin_exchange_rates
Feature: Synchronizing exchange rates in admin
    In order to keep exchange rates up to date
    As an Administrator
    I want to be able to synchronize exchange rates from external providers

    Background:
        Given the store operates on a single channel in "United States"
        And I am logged in as an administrator

    @ui @javascript @mink:panther
    Scenario: Synchronizing exchange rates by clicking the synchronize button
        When I browse exchange rates
        And I click the synchronize button
        Then I should see a success message
        And the exchange rates should be updated
