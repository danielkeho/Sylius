@applying_taxes
Feature: Not applying taxes for products without tax category
    In order to pay proper amount when buying goods without tax category
    As a Visitor
    I want to have correct taxes applied to my order

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And the store has a product "Symfony Mug" priced at "$30.00"

    @api @ui
    Scenario: Proper taxes for untaxed product
        Given I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$100.00"
        And there should be no taxes charged

    @api @ui
    Scenario: Proper taxes for untaxed product with quantity specified
        Given I added 3 products "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then my cart total should be "$300.00"
        And there should be no taxes charged

    @api @ui
    Scenario: Proper taxes for multiple untaxed products
        Given I added product "PHP T-Shirt" to the cart
        And I added product "Symfony Mug" to the cart
        When I check the details of my cart
        Then my cart total should be "$130.00"
        And there should be no taxes charged
