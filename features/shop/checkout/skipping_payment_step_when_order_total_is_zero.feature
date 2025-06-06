@checkout
Feature: Skipping payment selection when order total is zero
    In order to not select payment method when it is unnecessary
    As a Visitor
    I want to be redirect directly to order summary page after shipping selection

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$10.00"
        And the store ships everywhere for Free
        And the store has "SHL" shipping method with "$5.00" fee
        And there is a promotion "Holiday promotion"
        And the promotion gives "$10.00" discount to every order with quantity at least 1

    @api @ui
    Scenario: Seeing order summary after shipping selection when order total is zero
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "Free" shipping method
        And I complete the shipping step
        Then I should be on the checkout summary step
        And I should not see any information about payment method

    @api @ui
    Scenario: Seeing payment selection after shipping selection when order total is not zero
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "jon.snow@example.com"
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "SHL" shipping method
        And I complete the shipping step
        Then I should be on the checkout payment step
