@paying_for_order
Feature: Seeing payment method instructions after checkout
    In order to know how to pay for my order
    As a Customer
    I want to be informed about payment instructions for chosen payment method

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And the store ships everywhere for Free
        And the store has a payment method "Offline" with a code "OFFLINE"
        And it has instructions "Account number: 0000 1111 2222 3333"
        And I am a logged in customer

    @api @ui
    Scenario: Being informed about payment instructions
        Given this payment method is not using Payum
        And I have product "PHP T-Shirt" in the cart
        When I proceed with selecting "Offline" payment method
        And I confirm my order
        Then I should be informed with "Offline" payment method instructions

    @api @ui
    Scenario: Using Payum being informed about payment instructions
        Given I have product "PHP T-Shirt" in the cart
        When I proceed with selecting "Offline" payment method
        And I confirm my order
        Then I should be informed with "Offline" payment method instructions
