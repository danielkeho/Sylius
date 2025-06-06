@applying_shipping_fee
Feature: Apply correct shipping fee with taxes on order
    In order to pay proper amount for shipping
    As a Customer
    I want to have correct shipping fees and taxes applied to my order

    Background:
        Given the store operates on a single channel in "United States"
        And there is a zone "The Rest of the World" containing all other countries
        And the store ships to "Germany"
        And the store has "NA VAT" tax rate of 23% for "Clothes" within the "US" zone
        And the store has "Low tax" tax rate of 10% for "Clothes" for the rest of the world
        And the store has "Shipping NA VAT" tax rate of 23% for "Shipping Services" within the "US" zone
        And the store has "Shipping Low tax" tax rate of 10% for "Shipping Services" for the rest of the world
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And the store has "DHL" shipping method with "$10.00" fee within the "US" zone
        And the store has "DHL-World" shipping method with "$20.00" fee for the rest of the world
        And shipping method "DHL" belongs to "Shipping Services" tax category
        And shipping method "DHL-World" belongs to "Shipping Services" tax category
        And the store allows paying Offline
        And I am a logged in customer

    @api @ui
    Scenario: Proper shipping fee and tax
        Given I added product "PHP T-Shirt" to the cart
        And I addressed the cart
        And I chose "DHL" shipping method
        When I check the details of my cart
        Then my cart total should be "$112.30"
        And my cart taxes should be "$2.30"
        And my cart shipping total should be "$12.30"

    @api @ui
    Scenario: Proper shipping fee and tax after addressing
        Given I added product "PHP T-Shirt" to the cart
        And I addressed the cart to "Germany"
        And I chose "DHL-World" shipping method
        When I check the details of my cart
        Then my cart total should be "$122.00"
        And my cart taxes should be "$2.00"
        And my cart shipping total should be "$22.00"
