@checkout
Feature: Sign in to the store during checkout
    In order to sign during the addressing step
    As a Visitor
    I want to be able to sign in

    Background:
        Given the store operates on a single channel in "United States"
        And the store has a product "PHP T-Shirt" priced at "$19.99"
        And there is a customer "Francis Underwood" identified by an email "francis@underwood.com" and a password "whitehouse"

    @no-api @ui @javascript
    Scenario: Displaying login form if the customer has an account
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "francis@underwood.com"
        Then I should be able to log in

    @no-api @ui @javascript
    Scenario: Successful sign in
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "francis@underwood.com"
        And I specify the password as "whitehouse"
        And I sign in
        Then the login form should no longer be accessible

    @no-api @ui @javascript
    Scenario: Failure sign in
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the email as "francis@underwood.com"
        And I specify the password as "francis"
        And I sign in
        Then I should be notified about bad credentials

    @no-api @ui @mink:chromedriver
    Scenario: Successful sign in after omitting fill the email field
        Given I added product "PHP T-Shirt" to the cart
        And I am at the checkout addressing step
        When I specify the billing address for "Jon Snow" from "Ankh Morpork", "Frost Alley", "90210", "United States", "Texas"
        And I try to complete the addressing step
        And I specify the email as "francis@underwood.com"
        And I specify the password as "whitehouse"
        And I sign in
        Then I should be notified to resubmit the addressing form
        And I should not be notified that the form contains extra fields
        And address "Jon Snow", "Ankh Morpork", "Frost Alley", "90210", "United States", "Texas" should be filled as billing address
