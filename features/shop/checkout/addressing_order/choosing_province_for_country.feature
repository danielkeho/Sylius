@checkout
Feature: Choosing province for country
    In order to address an order properly
    As a Visitor
    I want to be able to fill addressing details choosing country with provinces

    Background:
        Given the store operates on a single channel in "United States"
        And country "United States" has the "New York" province with "NY" code
        And the store ships to "Nepal"
        And the store has a product "The Dark Knight T-Shirt" priced at "$49.99"
        And the store ships everywhere for Free

    @api @ui @javascript
    Scenario: Address an order with country and its province
        Given I added product "The Dark Knight T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "bruce.wayne@example.com"
        And I specify the billing address as "Gotham", "Mountain Drive", "1007", "United States" for "Bruce Wayne"
        And I specify billing country province as "New York"
        And I complete the addressing step
        Then I should be on the checkout shipping step

    @api @ui @mink:chromedriver
    Scenario: Address an order with country and its province and specify country without province for different billing address
        Given I added product "The Dark Knight T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "bruce.wayne@example.com"
        And I specify the billing address as "Nanda Parbat", "League of Assassins House", "11-333", "Nepal" for "Ra's al Ghul"
        And I specify the shipping address as "Gotham", "Mountain Drive", "1007", "United States" for "Bruce Wayne"
        And I specify shipping country province as "New York"
        And I complete the addressing step
        Then I should be on the checkout shipping step

    @api @ui @javascript
    Scenario: Address an order with country and its province and specify country with province for different billing address
        Given I added product "The Dark Knight T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "bruce.wayne@example.com"
        And I specify the billing address as "Gotham", "Mountain Drive", "1007", "United States" for "Bruce Wayne"
        And I specify billing country province as "New York"
        And I specify the shipping address as "Metropolis", "Clinton Str.", "344", "United States" for "Clark Kent"
        And I specify shipping country province as "New York"
        And I complete the addressing step
        Then I should be on the checkout shipping step

    @api @no-ui
    Scenario: Being unable to address an order with country without provinces and province from other country
        Given I added product "The Dark Knight T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "bruce.wayne@example.com"
        And I specify the billing address as "Nanda Parbat", "League of Assassins House", "11-333", "Nepal" for "Ra's al Ghul"
        And I specify billing country province as "New York"
        And I complete the addressing step
        Then I should be notified that selected province is invalid for billing address
        And I should still be on the checkout addressing step
