# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Axontis-API is a Laravel 12 application for an alarm system and security company CRM. It manages clients, contracts, installations, devices, orders, and payments. The application uses PostgreSQL, Redis for cache/sessions/queues, and includes integrations with DocuSign (signatures), Stripe (payments), and multiple email providers.

## Common Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Run development server
php artisan serve

# Run queue worker (for background jobs)
php artisan queue:work

# Run scheduler (for scheduled tasks)
php artisan schedule:work
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Fresh migration with seeding
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status
```

### Testing
```bash
# Run all tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite Unit
./vendor/bin/phpunit --testsuite Feature

# Run single test
./vendor/bin/phpunit tests/Feature/ExampleTest.php
```

### Cache & Optimization
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Cache for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Code Quality
```bash
# Run Pint (Laravel Prettier)
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse
```

## Architecture

### UUID-Based Models

All models extend `App\Models\Model` and use UUIDs as primary keys instead of auto-incrementing integers. The `id` column is hidden from JSON responses, and `uuid` is the public identifier.

**Key traits:**
- `HasUuid`: Adds UUID generation and route key binding
- `FromUuid`: Enables finding models by UUID instead of ID in route model binding

**Route model binding:** Models are resolved via UUID using the `FromUuid` trait. Route parameters use UUIDs, not database IDs.

### Polymorphic Relationships

The application uses extensive polymorphic relationships:
- `File`: Attached to any entity via `fileable_type` and `fileable_id`
- `Communication`: Tracks notifications to any entity via `communicable_type` and `communicable_id`
- `Signature`: Links signers and signable entities

### Extended Properties System

Models using the `HasProperties` trait support dynamic key-value properties stored in the `properties` table. This allows flexible, schema-less data storage without modifying table structures.

```php
$client->setProperty('custom_field', 'value', 'string');
$client->getProperty('custom_field');
$client->getAllProperties();
```

### File Management System

A centralized file management system is available through the `ManagesFiles` trait and `FileService`:

**Route macros:**
- `Route::fileRoutes($prefix, $controller)`: Registers document routes for web
- `Route::apiFileRoutes($prefix, $controller)`: Registers document routes for API
- `Route::resourceWithFiles($name, $controller)`: Combines resource routes with file management

**Controller methods (via ManagesFiles trait):**
- `uploadDocument()`, `uploadMultipleDocuments()`
- `deleteDocument()`, `deleteMultipleDocuments()`
- `renameDocument()`, `downloadDocument()`, `viewDocument()`
- `getDocuments()`

**Supported storage:** Local (default), S3, Cloudflare R2

### Communication & Notification System

The `Communication` model tracks all outbound communications (email, SMS, WhatsApp) sent to clients and users. It provides:
- Channel mapping (email, phone, sms, whatsapp, other)
- Status tracking (pending, sent, delivered, failed)
- Provider tracking (resend, mailgun, brevo, twilio)
- Extensive scopes for filtering

**Listeners:**
- `LogNotificationToCommunication`: Logs notifications to communications table
- `EmailFailoverListener`: Handles email provider failures
- `EmailSentListener`: Tracks successful emails
- `NotificationFailureAlertListener`: Alerts on critical notification failures

**Mail configuration:** Uses failover transport with multiple providers (Brevo, Resend, Mailgun).

### Payment System

Uses a provider-based architecture via `PaymentManager`:

**Providers:**
- `StripeProvider`: Default Stripe integration
- `CmiProvider`: Alternative provider

**Workflow:**
1. `PaymentService::initializePayment()` creates Payment record and Stripe intent
2. Frontend uses Stripe.js to collect payment (PCI-DSS compliant)
3. Webhook `/webhooks/stripe` updates payment status
4. Refund capability via `PaymentService::refundPayment()`

### Signature System (DocuSign)

`DocuSignService` handles electronic signatures:
- JWT-based authentication with token caching
- HMAC webhook signature validation
- Embedded signing URLs for contracts
- Retry logic with exponential backoff
- Document download after signing

**Webhook:** `/signature/webhook/{provider}` handles signature completion events.

### User Roles & Permissions

Uses PHP enums for role-based access control (`UserRole` enum):
- `client`: External customers (access to client portal)
- `technician`: Field technicians
- `operator`: Support staff
- `manager`: Team leads (can manage operators, technicians)
- `administrator`: Full system access

**Middleware:**
- `EnsureUserHasRole`: Checks role via `role:manager,administrator` parameter
- `ClientActiveMiddleware`: Verifies client is active before portal access

### Route Organization

- `routes/api.php`: Public API endpoints, webhooks, dashboard data
- `routes/web.php`: Inertia-rendered web pages, CRM routes, client portal

**CRM routes** (`/crm/*`): Require authentication and appropriate roles
**Client portal** (`/client/*`, `/installation/*`): Protected by `client.active` middleware

### Service Layer Pattern

Business logic is encapsulated in services:
- `ClientService`: Client lifecycle and offer calculation
- `ContractService`: Contract generation and management
- `PaymentService`: Payment processing via providers
- `SignatureService`: Electronic signature coordination
- `DocuSignService`: DocuSign API wrapper
- `FileService`: Storage abstraction layer
- `ArrivalService`: Order arrival processing
- `InstallationService`: Installation scheduling and management

## Environment Configuration

Key `.env` variables:
- `DB_CONNECTION=pgsql` / `DB_*`: Database connection (PostgreSQL)
- `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`: Redis configuration
- `MAIL_MAILER=failover`: Email failover (Brevo → Resend)
- `FILESYSTEM_DISK=local`: Storage disk (local, s3, r2)
- `STRIPE_*`: Stripe payment configuration
- `DOCUSIGN_*`: DocuSign integration
- `SENTRY_LARAVEL_DSN`: Error tracking

## Deployment

Production deployment uses zero-downtime strategy via GitHub Actions. See `DEPLOYMENT.md` for details.

Server runs with:
- Supervisor for queue workers and scheduler
- Nginx + PHP-FPM
- Redis for cache/sessions/queues
- PostgreSQL database
