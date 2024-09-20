

# REST API Application
=======================

## Overview

This is a RESTful API application designed to manage account operations. The API provides endpoints for performing transfers between accounts, retrieving account balances, and resetting account data.

## Endpoints

### 1. Handle Transfer

* **URL:** `/transfer`
* **Method:** `POST`
* **Request Body:**
	+ `origin`: The ID of the origin account
	+ `destination`: The ID of the destination account
	+ `amount`: The amount to transfer
* **Response:**
	+ `origin`: The updated balance of the origin account
	+ `destination`: The updated balance of the destination account

### 2. Get Account Balance

* **URL:** `/balance/{account_id}`
* **Method:** `GET`
* **Path Parameters:**
	+ `account_id`: The ID of the account to retrieve the balance for
* **Response:**
	+ `balance`: The current balance of the account

### 3. Reset Account Data

* **URL:** `/reset`
* **Method:** `POST`
* **Response:**
	+ `message`: A confirmation message indicating that the account data has been reset

## Notes

* The API uses JSON format for request and response bodies.
* The API uses standard HTTP status codes to indicate the outcome of each request.
* The API is designed to be secure and follows best practices for RESTful API development.

## Dependencies

* PHP 8.2 or later
* Symfony 7.1 or later
* Composer

## Installation

1. Clone the repository to your local machine.
2. Run `composer install` to install dependencies.
3. Configure the API by creating a `.env` file with your environment variables.
4. Start the API by running `php bin/console server:start`.

## Testing

The API includes a set of unit tests to ensure its functionality. To run the tests, execute the following command:

`php bin/phpunit`