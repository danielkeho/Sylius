@checkout
Feature: Skipping payment selection when order total is zero after applying coupon
    In order not to select payment method when it is unnecessary
    As a Customer
    I want to be redirected directly to order summary page after shipping selection when my order total is zero

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$10.00"
        And the store ships everywhere for Free
        And the store has "SHL" shipping method with "$5.00" fee
        And the store has promotion "Holiday promotion" with coupon "HOLIDAYPROMO"
        And the promotion gives "$10.00" discount to every order with quantity at least 1
        And the store allows paying Offline
        And I am a logged in customer

    @no-api @ui
    Scenario: Seeing order summary after shipping selection when order total is zero
        Given I added product "PHP T-Shirt" to the cart
        And I applied the coupon with code "HOLIDAYPROMO"
        When I am at the checkout addressing step
        And I specify the billing address as "Ankh Morpork", "Frost Alley", "90210", "United States" for "Jon Snow"
        And I complete the addressing step
        And I select "Free" shipping method
        And I complete the shipping step
        Then I should be on the checkout summary step
        And I should not see any information about payment method
