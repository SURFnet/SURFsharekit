# Surf ShareKit CMS

## Project Overview

Surf ShareKit CMS is a PHP-based content management backend built on top of Silverstripe and tailored for managing research outputs and related metadata within the SURF ShareKit ecosystem. It exposes a RESTful API (documented with Swagger) consumed by a separate frontend client and external services.

Core technologies:

- Application framework: `silverstripe/framework` (Silverstripe CMS 4.x)
- Language: `php` (>= 7.2.24, typically 7.4 in local Docker)
- Web server and runtime: `apache` within Docker (`brettt89/silverstripe-web` images)
- Database: `mysql` (via `SS_DATABASE_*` configuration)
- HTTP client: `guzzlehttp/guzzle` (`7.9.3`)
- Mailing: `symfony/mailer` (`v6.4.21`), `async-aws/ses` (`1.11.0`), `aws/aws-sdk-php` (`3.337.3`)
- Logging: `monolog/monolog` (`3.9.0`) with `psr/log` (`3.0.2`)
- Authentication and integration:
    - `bigfork/silverstripe-oauth` (`2.2.2`)
    - `bigfork/silverstripe-oauth-login` (`2.3.1`)
    - `firebase/php-jwt` (`v6.11.1`)
- Utility and framework helpers:
    - `symfony/config` (`v6.4.14`)
    - `illuminate/support` (`v12.12.0`) and related `illuminate/*` packages
    - `carbonphp/carbon-doctrine-types` (`3.2.0`)
    - `brick/math` (`0.12.3`)

The project is containerised with Docker and uses Composer as its dependency manager.

## Prerequisites

To build and run the project locally, you will need:

- PHP
    - Local PHP version set to `7.4.*` (to match the Silverstripe constraints specified in `composer.json`, currently `php` `>=7.2.24`).
- Composer
    - `composer` installed to manage PHP dependencies.
- Docker
    - `docker` and `docker compose` (or `docker-compose`) installed and running.
- Database
    - A MySQL-compatible database reachable from the Docker containers (for local dev this is often provided by Docker Compose).
- External services and access
    - Amazon SES credentials for outbound email.
    - Amazon S3 or compatible object storage credentials for file storage.
    - SURFconext OAuth client credentials for authentication.
    - DOI service account credentials if DOI generation is enabled.
- Package manager for Node.js (if needed for any frontend assets)
    - `npm` (present in most setups, but this project is primarily PHP/Silverstripe; only required if additional tooling or front-end build steps are used).

## Configuration

All sensitive and environment-specific settings are configured via environment variables in `.env`. For local development:

1. Copy `.env.example` to `.env` in the project root.
2. Update all required variables with environment-specific values.
3. In `public`, copy `.htaccess.example` to `.htaccess`.

Key environment variables:

### General

- `SS_BASE_URL` – Base URL for the CMS and API, for example `http://localhost:8080`.

### Database credentials

- `SS_DATABASE_CLASS` – Database driver class, typically `MySQLDatabase`.
- `SS_DATABASE_SERVER` – Database host, e.g. `localhost` or a Docker service name.
- `SS_DATABASE_USERNAME` – Database user.
- `SS_DATABASE_PASSWORD` – Database password.
- `SS_DATABASE_NAME` – Database name.

### Email credentials (Amazon SES)

- `SES_SMTP_USERNAME` – AWS SES SMTP username.
- `SES_SMTP_PASSWORD` – AWS SES SMTP password.
- `ADMIN_EMAIL` – General from-address used as the admin email sender.

### Logging

- `LOCAL_LOG_PATH` – Filesystem path where application logs should be written (including filename, e.g. `/var/log/sharekit/app.log`).
- `LOG_LEVEL` – Minimum log level, e.g. `debug`, `info`, `warning`, `error`.

### Environment settings

- `SS_ENVIRONMENT_TYPE` – Silverstripe environment type, e.g. `dev`, `test`, `live`.
- `APPLICATION_ENVIRONMENT` – Application environment string, typically aligned with `SS_ENVIRONMENT_TYPE`, e.g. `dev`, `staging`, `prod`.
- `SS_DEFAULT_ADMIN_USERNAME` – Initial CMS admin username.
- `SS_DEFAULT_ADMIN_PASSWORD` – Initial CMS admin password.
- `APACHE_RUN_USER` – Apache user (e.g. `apache`), used inside the Docker image.

### S3 / Object store credentials

- `AWS_REGION` – AWS region, e.g. `eu-west-1`.
- `AWS_BUCKET_NAME` – Bucket name used for storing assets.
- `AWS_ACCESS_KEY_ID` – AWS access key.
- `AWS_SECRET_ACCESS_KEY` – AWS secret key.

### Migration database (for migration testing)

- `MIGRATION_DB_HOST` – Host for the source database used in migration tests.
- `MIGRATION_DB_USER` – Username for the migration database.
- `MIGRATION_DB_PASSWORD` – Password for the migration database.
- `MIGRATION_DB_DATABASE` – Database name for migration source.

### SURFconext (OAuth)

- `CONEXT_URL` – Base URL for SURFconext.
- `CONEXT_CLIENT_ID` – SURFconext OAuth client ID.
- `CONEXT_CLIENT_SECRET` – SURFconext OAuth client secret.

### Client URL

- `FRONTEND_BASE_URL` – Base URL of the ShareKit client application, used for generating direct URLs.

### DOI configuration

- `DOI_SERVER` – DOI service base URL or endpoint.
- `DOI_PREFIX` – DOI prefix assigned to the organisation.
- `DOI_USER` – Username for DOI service.
- `DOI_PASSWORD` – Password for DOI service.

### Private key for file downloads

Used to generate secure, temporary download links for protected files:

- `FILE_DOWNLOAD_PRIVATE_KEY` – Private key used for encryption/signing.
- `FILE_DOWNLOAD_IV` – Initialisation vector for the cipher.
- `FILE_DOWNLOAD_CIPHER` – Cipher algorithm, e.g. `AES-256-CBC`.

## Running Locally

Follow these steps to get a local development environment running:

1. Configure environment:
    - Copy `.env.example` to `.env` in the project root: `cp .env.example .env`
    - Update all variables as described in the Configuration section.
    - In the `public` directory, copy `.htaccess.example` to `.htaccess`: `cp public/.htaccess.example public/.htaccess`
    - In the `security.php` file, comment the function `getAuthenticators()`
2. Install dependencies:
    - Run `composer install` in the project root.
3. Start Docker environment:
    - From the project root, run `docker compose up --build` (or `docker-compose up --build` depending on your Docker version).
    - If a newer Silverstripe version is required, update the base image in `Dockerfile` (for example adjust `FROM brettt89/silverstripe-web:7.1-apache` to a version that matches the Silverstripe/PHP stack you are using, as listed on Docker Hub).
4. Build Silverstripe database and flush caches:
    - Once containers are up, open `http://localhost:8080/dev/build?flush=1` in your browser.
    - Wait for the build to complete.
5. Login to CMS:
    - Go to the CMS URL, typically `http://localhost:8080/admin`.
    - Log in with `SS_DEFAULT_ADMIN_USERNAME` and `SS_DEFAULT_ADMIN_PASSWORD` as configured in `.env`.
6. Ask college for a database dump and restore it into the database.

After these steps, the CMS and API should be available locally via the configured `SS_BASE_URL`.

## API Documentation & Tooling

- Swagger / OpenAPI:
    - A Swagger definition is provided at `public/swagger.json`.
    - When running locally with default Docker configuration, it is typically accessible at `http://localhost:8080/swagger.json`.
    - You can import this JSON into tools like Postman or Bruno.
- Request collection tools:
    - You can create and share collections in `Postman` or `Bruno` by importing the Swagger file.
    - Optionally, keep API collection files under `docs` (or a dedicated directory) if you maintain shared collections.

## Testing

The project uses PHPUnit for automated testing.

- Framework:
    - `phpunit/phpunit` (configured via `phpunit.xml` and `phpunitbootstrap.php`).
- Types of tests:
    - Unit tests for PHP classes and services.
    - Integration tests for core application workflows and API endpoints (depending on the tests present in the `app` or `tests` directories).
- Running tests:

1. Ensure dependencies are installed:
    - `composer install`
2. Run the full test suite:
    - `vendor/bin/phpunit`
3. (Optional) Run with a specific configuration or filter:
    - `vendor/bin/phpunit --filter SomeTestClass`
    - `vendor/bin/phpunit --testsuite <suite-name>` if suites are defined in `phpunit.xml`.

Ensure the database and environment variables used by tests are configured. You may set up separate testing credentials (for example, a dedicated test database).

## Project specific information

- Silverstripe-specific resources:
    - For more information on Silverstripe concepts (DataObjects, Controllers, Templates, ORM), see `https://docs.silverstripe.org/en/4/getting_started/`.
- Webhook processing:
    - The file `WebhookProcessor.php` at the project root indicates there may be custom integration for processing inbound webhooks (e.g. external systems updating records). Consult that file and related configuration for details.
- CI/CD:
    - `.gitlab-ci.yml` and `.gitlab-ci` indicate a GitLab CI pipeline is configured for automated testing, code quality checks (e.g. `phpcs`, static analysis), and deployment.
- Quality and static analysis:
    - `sonar-project.properties` configures SonarQube analysis.
    - `qodana.sarif.json` suggests integration with JetBrains Qodana.

## Packages

Below are some key Composer packages grouped by concern (based on `composer.json` and project requirements):

### Core framework and CMS

- `silverstripe/framework` – Silverstripe CMS framework.
- `silverstripe/admin` – Silverstripe CMS admin UI.
- `silverstripe/assets` – Asset management.
- `silverstripe/graphql` – GraphQL support (backed by `.graphql-generated` and `public/_graphql`).

### HTTP and networking

- `guzzlehttp/guzzle` (`7.9.3`) – HTTP client for external integrations.
- `guzzlehttp/psr7` (`2.7.1`) – PSR-7 HTTP message implementation.
- `guzzlehttp/promises` (`2.2.0`) – Promises implementation used by Guzzle.

### Logging and monitoring

- `monolog/monolog` (`3.9.0`) – Application logging, integrated via PSR-3.
- `psr/log` (`3.0.2`) – Logging interfaces.

### Email and messaging

- `symfony/mailer` (`v6.4.21`) – Abstraction for sending emails.
- `async-aws/core` (`1.25.0`) – Core async AWS SDK components.
- `async-aws/ses` (`1.11.0`) – Amazon SES integration.
- `aws/aws-sdk-php` (`3.337.3`) – AWS SDK for PHP (S3, SES, and other AWS services).
- `aws/aws-crt-php` (`v1.2.7`) – AWS Common Runtime for enhanced performance.

### Authentication and security

- `bigfork/silverstripe-oauth` (`2.2.2`) – OAuth integration for Silverstripe.
- `bigfork/silverstripe-oauth-login` (`2.3.1`) – OAuth-based login module (e.g. SURFconext).
- `firebase/php-jwt` (`v6.11.1`) – JSON Web Token handling (signing and verifying tokens).

### Utilities and helpers

- `symfony/config` (`v6.4.14`) – Configuration loading and management.
- `composer/ca-bundle` (`1.5.6`) – CA bundle utility for HTTPS.
- `composer/installers` (`v2.3.0`) – Custom package installers.
- `composer/semver` (`3.4.3`) – Semantic versioning utilities.
- `brick/math` (`0.12.3`) – High-precision math library.
- `carbonphp/carbon-doctrine-types` (`3.2.0`) – Carbon date/time integration for Doctrine types.
- `illuminate/support`, `illuminate/collections`, `illuminate/macroable`, `illuminate/contracts`, `illuminate/conditionable` (all `v12.12.0`) – Laravel/Illuminate helper utilities used for collections, macros, and contracts.
- `egulias/email-validator` (`4.0.4`) – Email address validation.
- `embed/embed` (`v4.4.15`) – URL embedding/parser library.
- `myclabs/deep-copy` (`1.13.1`) – Deep copy utility.

This list is not exhaustive, but highlights the most relevant packages involved in core application behaviour, integrations, and tooling.