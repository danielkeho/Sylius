@applying_taxes
Feature: Apply correct taxes for products with different tax rates for different zones
    In order to pay proper amount when buying goods having different tax rates for different zones
    As a Customer
    I want to have correct taxes applied to my order

    Background:
        Given the store operates on a single channel in "United States"
        And there is a zone "The Rest of the World" containing all other countries
        And the store ships to "Germany"
        And the store ships everywhere for Free
        And default tax zone is "US"
        And the store has "No tax" tax rate of 0% for "Clothes" within the "US" zone
        And the store has "Low VAT" tax rate of 5% for "Mugs" within the "US" zone
        And the store has "VAT" tax rate of 23% for "Clothes" for the rest of the world
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And it belongs to "Clothes" tax category
        And the store has a product "Symfony Mug" priced at "$50.00"
        And it belongs to "Mugs" tax category
        And I am a logged in customer

    @api @ui
    Scenario: Displaying correct tax before specifying shipping address
        Given I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$100.00"
        And there should be no taxes charged

    @api @ui
    Scenario: Displaying correct tax after specifying billing address
        Given I added product "PHP T-Shirt" to the cart
        And I addressed the cart to "Germany"
        When I check the details of my cart
        Then my cart total should be "$123.00"
        And my cart taxes should be "$23.00"

    @api @ui
    Scenario: Displaying correct taxes for multiple products after specifying billing address
        Given I added 3 products "PHP T-Shirt" to the cart
        And I addressed the cart to "Germany"
        When I check the details of my cart
        Then my cart total should be "$369.00"
        And my cart taxes should be "$69.00"

    @api @ui
    Scenario: Displaying correct taxes for multiple products from different zones before specifying shipping address
        Given I added product "PHP T-Shirt" to the cart
        And I added 2 products "Symfony Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$205.00"
        And my cart taxes should be "$5.00"

    @api @ui
    Scenario: Displaying correct taxes for multiple products from different zones after specifying billing address
        Given I added product "PHP T-Shirt" to the cart
        And I added 2 products "Symfony Mug" to the cart
        And I addressed the cart to "Germany"
        When I check the details of my cart
        Then my cart total should be "$223.00"
        And my cart taxes should be "$23.00"
