@paying_for_order
Feature: Having good number of items in changing payment method page
    In order to verify that I am changing the payment method of correct order
    As a Visitor
    I want to see correct details about my order on changing the payment method page

    Background:
        Given the store operates on a single channel in "United States"
        And the store allows paying "Offline"
        And the store allows paying "Cash on delivery"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free

    @no-api @ui
    Scenario: Seeing correct quantity on payment retry page
        Given I added 2 products "PHP T-Shirt" to the cart
        When I complete addressing step with email "john@example.com" and "United States" based billing address
        And I have proceeded selecting "Cash on delivery" payment method
        And I have confirmed order
        And I go to the change payment method page
        Then I should see 2 as number of items
