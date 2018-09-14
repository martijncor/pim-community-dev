Feature: Volume statistics of the customers
  In order to better know the usage of the PIM
  As Akeneo Company
  I want to monitor anonymously volumes of the customers

  @acceptance-back
  Scenario: Gather customers statistics about the number of channels
    Given a catalog with 3 channels
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 3 channels for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of locales
    Given a catalog with 6 locales
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 6 locales for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of products
    Given a catalog with 10 products
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 10 products for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of product models
    Given a catalog with 8 product models
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 8 product models for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of variant products
    Given a catalog with 5 variant products
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 5 variant products for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of families
    Given a catalog with 7 families
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 7 families for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of users
    Given a catalog with 22 users
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 22 users for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of categories
    Given a catalog with 5 categories
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 5 categories for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of category trees
    Given a catalog with 7 category trees
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 7 category trees for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the maximum of categories in one category
    Given a catalog with 8 categories in one category
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a maximum of 8 categories in one category for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the maximum of category levels
    Given a catalog with 12 category levels
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a maximum of 12 category levels for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of product values
    Given a catalog with 487520 product values
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 487520 product values for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the average of product values by product
    Given a product with 587 product values
    And a product model with 565 product values
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores an average of 576 product values for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the average of product values per family
    Given a family with 25 product values
    And a family with 45 product values
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores an average of 35 product values per family for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the maximum of product values per family
    Given a family with 25 product values
    And a family with 45 product values
    When statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a maximum of 45 product values per family for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the number of useable as grid filter attribute
    Given a catalog with 10 useable as grid filter attributes
    When attribute statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores a number of 10 useable as grid filter attribute for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the average of localizable attributes per family
    Given a family with 10 localizable attributes
    And a family with 20 localizable attributes
    When attribute statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores an average of 15 localizable attributes per family for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the average of scopable attributes per family
    Given a family with 10 scopable attributes
    And a family with 20 scopable attributes
    When attribute statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores an average of 15 scopable attributes per family for this customer

  @acceptance-back
  Scenario: Gather customers statistics about the average of localizable and scopable attributes per family
    Given a family with 10 localizable and scopable attributes
    And a family with 20 localizable and scopable attributes
    When attribute statistics of the customer's catalog are collected
    Then Akeneo statistics engine stores an average of 15 localizable and scopable attributes per family for this customer
