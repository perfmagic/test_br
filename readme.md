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

    Create a local environment file by copying the `.env` file:

    ```bash
    cp .env .env.local
    ```

    Open `.env.local` and configure your `DATABASE_URL`. For example, for MySQL:

    ```
    DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
    ```

4.  **Generate JWT keys:**

    ```bash
    php bin/console lexik:jwt:generate-keypair
    ```
    This command will generate the private and public keys for you. If you set a passphrase during generation, be sure to update the `JWT_PASSPHRASE` in your `.env.local` file.

5.  **Create the database and run migrations:**

    ```bash
    php bin/console doctrine:database:create
    php bin/console doctrine:migrations:migrate
    ```

## Running the application

You can run the application using the Symfony CLI:

```bash
symfony server:start
```
