@checkout
Feature: Sorting payment method selection
    In order to see the most suitable payment methods first
    As a Visitor
    I want to have them already sorted

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "Targaryen T-Shirt" priced at "$19.99"
        And the store allows shipping with "Aardvark Stagecoach"
        And the store allows paying with "Bank transfer" at position 0
        And the store allows paying with "Cash on Delivery" at position 2
        And the store allows paying with "Offline" at position 1

    @api @ui
    Scenario: Seeing payment methods sorted
        Given I added product "Targaryen T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "Aardvark Stagecoach" shipping method
        And I complete the shipping step
        Then I should have "Bank transfer" payment method available as the first choice
        And I should have "Cash on Delivery" payment method available as the last choice
