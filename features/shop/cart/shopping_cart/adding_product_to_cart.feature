@shopping_cart
Feature: Adding a simple product to the cart
    In order to select products for purchase
    As a Visitor
    I want to be able to add simple products to cart

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "T-Shirt banana" priced at "$12.54"
        And the store ships everywhere for free

    @api @ui
    Scenario: Adding a product to the cart as a logged in customer
        Given I am a logged in customer
        And the store has a product "Oathkeeper" priced at "$99.99"
        And I added this product to the cart
        When I check the details of my cart
        Then there should be one item in my cart
        And this item should have name "Oathkeeper"

    @api @no-ui
    Scenario: Adding a simple product to the cart after picked up more than one cart
        When I pick up my cart
        And I pick up my cart again
        And I add this product to the cart
        Then I should be on my cart summary page
        And I should be notified that the product has been successfully added
        And there should be one item in my cart
        And this item should have name "T-Shirt banana"

    @api @ui
    Scenario: Increasing quantity of an item in cart by adding the product again
        Given I am a logged in customer
        And I added product "T-Shirt banana" to the cart
        And I added this product to the cart again
        When I check the details of my cart
        Then I should see "T-Shirt banana" with quantity 2 in my cart
