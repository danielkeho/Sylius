@receiving_discount
Feature: Receiving percentage discount on products from specific price range
    In order to pay less while buying goods from specific price range
    As a Customer
    I want to receive discount for my purchase

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$100.00"
        And the store has a product "PHP Mug" priced at "$20.00"
        And the store has a product "PHP Sticker" priced at "$5.00"
        And there is a promotion "Christmas sale"
        And I am a logged in customer

    @api @ui
    Scenario: Receiving percentage discount on a single item fulfilling minimum price criteria
        Given this promotion gives "10%" off on every product with minimum price at "$50.00"
        And I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$90.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item with price equal to filter minimum criteria
        Given this promotion gives "10%" off on every product with minimum price at "$100.00"
        And I added product "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$90.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item fulfilling maximum price criteria
        Given this promotion gives "10%" off on every product with maximum price at "$50.00"
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$2.00"
        And my cart total should be "$18.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item with price equal to filter maximum criteria
        Given this promotion gives "10%" off on every product with maximum price at "$20.00"
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$2.00"
        And my cart total should be "$18.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item fulfilling range price criteria
        Given this promotion gives "50%" off on every product priced between "$15.00" and "$50.00"
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$10.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item with price equal to filter range minimum criteria
        Given this promotion gives "50%" off on every product priced between "$20.00" and "$50.00"
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$10.00"

    @api @ui
    Scenario: Receiving percentage discount on a single item with price equal to filter range maximum criteria
        Given this promotion gives "50%" off on every product priced between "$15.00" and "$20.00"
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$10.00"
        And my cart total should be "$10.00"

    @api @ui
    Scenario: Receiving percentage discount on multiple items fulfilling range price criteria
        Given this promotion gives "50%" off on every product priced between "$50.00" and "$150.00"
        And I added 3 products "PHP T-Shirt" to the cart
        When I check the details of my cart
        Then its price should be decreased by "$150.00"
        And my cart total should be "$150.00"

    @api @ui
    Scenario: Receiving percentage discount only on items that fit price range criteria
        Given this promotion gives "25%" off on every product priced between "$30.00" and "$150.00"
        And I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$25.00"
        And product "PHP Mug" price should not be decreased
        And my cart total should be "$95.00"

    @api @ui
    Scenario: Receiving different discounts on items from different price ranges
        Given this promotion gives "10%" off on every product with minimum price at "$80.00"
        And there is a promotion "Mugs promotion"
        And it gives "90%" off on every product priced between "$10.00" and "$90.00"
        And there is a promotion "Stickers promotion"
        And it gives "20%" off on every product with maximum price at "$10.00"
        And I added product "PHP T-Shirt" to the cart
        And I added product "PHP Mug" to the cart
        And I added product "PHP Sticker" to the cart
        When I check the details of my cart
        Then product "PHP T-Shirt" price should be decreased by "$10.00"
        And product "PHP Mug" price should be decreased by "$18.00"
        And product "PHP Sticker" price should be decreased by "$1.00"
        And my cart total should be "$96.00"
