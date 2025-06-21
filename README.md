# BileMo
Project 7 OpenClassrooms
BileMo is a B2B mobile phone showcase platform. Instead of selling phones directly to users, BileMo provides an API that allows partner platforms to access the full catalog of mobile phones and manage their own users.

This project exposes a RESTful API using Symfony, following Richardson Maturity Model level 3. The API is secured using JWT authentication.

## Features

- List all available BileMo products
- Get detailed information about a specific product
- List users related to a specific client
- Get details of a single user
- Create a new user (linked to the client)
- Delete a user (created by the client)
- JWT Authentication for clients
- HATEOAS implemented for navigation
- JSON response format
- HTTP caching

## Stack

- PHP 8.2
- Symfony 7.3
- Doctrine ORM
- JWT Authentication (LexikJWTAuthenticationBundle)
- HATEOAS (willdurand/hateoas)
- API Documentation with NelmioApiDocBundle
- Serialization with JMSSerializer
- MySQL
- Postman for testing

## Getting Started

### Prerequisites

- PHP 8.2
- Composer
- Symfony CLI (recommended)
- A running database (MySQL or PostgreSQL)
- OpenSSL (for generating keys)

### Installation

```bash
git clone https://github.com/Esthoro/BileMo.git
cd bilemo

composer install

# Create your .env.local and set your DB credentials
cp .env .env.local

# Generate JWT keys
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

# Create database and schema
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Load sample data (optional)
php bin/console doctrine:fixtures:load
```

### Running the API

```bash
symfony server:start
```

Or:

```bash
php -S localhost:8000 -t public
```

## Authentication

The API uses JWT authentication. To obtain a token:

```http
POST /api/login_check

{
  "username": "client@example.com",
  "password": "password"
}
```

Use the token in the Authorization header for all requests:

```
Authorization: Bearer your_jwt_token
```

## API Documentation

- Nelmio documentation available at:
  ```
  /api/doc
  ```
- Documentation includes authentication method, endpoints, and sample payloads.

## License

This project is licensed under the MIT License.
