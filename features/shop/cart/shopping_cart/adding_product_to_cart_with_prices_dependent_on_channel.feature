@shopping_cart
Feature: Adding a product to cart with prices dependent on a channel
    In order to buy products in correct prices
    As a Customer
    I want to add products to my cart with prices dependent on visited channel

    Background:
        Given the store has currency "EUR"
        And the store has currency "GBP"
        And the store operates on a channel named "Web-EU" in "EUR" currency and with hostname "web-eu"
        And that channel allows to shop using "EUR" and "GBP" currencies
        And the store operates on another channel named "Web-GB" in "GBP" currency and with hostname "web-gb"
        And that channel allows to shop using "EUR" and "GBP" currencies
        And the store has a product "Leprechaun's Gold" priced at "€10.00" in "Web-EU" channel
        And this product is also priced at "£15.00" in "Web-GB" channel
        And I am a logged in customer

    @api @ui
    Scenario: Buying a product in default currency for browsed channel
        Given I changed my current channel to "Web-EU"
        And I added product "Leprechaun's Gold" to the cart
        When I check the details of my cart
        Then I should see "Leprechaun's Gold" with unit price "€10.00" in my cart

    @api @ui
    Scenario: Buying a product with proper price after channel change
        Given I changed my current channel to "Web-GB"
        And I added product "Leprechaun's Gold" to the cart
        When I check the details of my cart
        Then I should see "Leprechaun's Gold" with unit price "£15.00" in my cart
