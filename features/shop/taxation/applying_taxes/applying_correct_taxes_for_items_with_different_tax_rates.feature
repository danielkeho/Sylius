@applying_taxes
Feature: Apply correct taxes for items with different tax rates
    In order to pay proper amount when buying goods from different tax categories
    As a Visitor
    I want to have correct taxes applied to my order

    Background:
        Given the store operates on a single channel in "United States"
        And default tax zone is "US"
        And the store has "VAT" tax rate of 23% for "Clothes" within the "US" zone
        And the store has "Low VAT" tax rate of 5% for "Mugs" within the "US" zone
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And it belongs to "Clothes" tax category
        And the store has a product "Symfony Mug" priced at "$50.00"
        And it belongs to "Mugs" tax category

    @api @ui
    Scenario: Proper taxes for different taxed products
        Given I added product "PHP T-Shirt" to the cart
        And I added product "Symfony Mug" to the cart
        And I check the details of my cart
        Then my cart total should be "$175.50"
        And my cart taxes should be "$25.50"

    @api @ui
    Scenario: Proper taxes for multiple products with different tax rate
        Given I added 3 products "PHP T-Shirt" to the cart
        And I added 4 products "Symfony Mug" to the cart
        And I check the details of my cart
        Then my cart total should be "$579.00"
        And my cart taxes should be "$79.00"
