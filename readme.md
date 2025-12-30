# Brave Project

This is a Symfony project.

## Prerequisites

*   PHP 8.2 or higher
*   Composer
*   Symfony CLI

## Setup

1.  **Clone the repository:**

    ```bash
    git clone https://github.com/perfmagic/test_br.git
    cd test_br
    ```

2.  **Install dependencies:**

    ```bash
    composer install
    ```

3.  **Configure environment variables:**

4.  **Generate JWT keys:**

    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```
    This command will generate the private and public keys for you. If you set a passphrase during generation, be sure to update the `JWT_PASSPHRASE` in your `.env.dev` file.

5.  **Create the database and run migrations:**

    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

6.  Optional: import ready DB dump (faster start)

    A MySQL dump `db.sql` is included in the repository root. Importing it can simplify the first run (pre‑populated schema/data):

    ```bash
    # Adjust connection parameters as needed
    mysql < db.sql
    ```
    If you import the dump, you usually don’t need to run migrations separately.

## Running the application

You can run the application using the Symfony CLI:

```bash
symfony server:start
```

## Manual API testing in PhpStorm (HTTP Client)

This project includes an HTTP client requests file for manual testing:

- `api-users.http` — contains example requests for obtaining a JWT and calling the `/v1/api/users` endpoints (POST, GET, PUT, DELETE), including negative cases.

How to use:

1. Open the file in PhpStorm.
2. Ensure the app is running and reachable (adjust base URL inside the file if needed).
3. Click the green gutter icon next to a request to execute it. The login request stores the JWT token for subsequent requests.
