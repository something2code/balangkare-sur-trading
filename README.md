# Balangkare Sur Trading

A starter PHP/MySQL marketplace for the capstone proposal: local farmers can list harvests, buyers can request quantities, and both sides can coordinate payment and fulfillment directly.

## Stack

- HTML and CSS for accessible, responsive pages
- JavaScript for the mobile menu, Farm Guide, and live product conversations
- PHP 8+ for sessions, validation, marketplace, and order handling
- MySQL/MariaDB for accounts, products, orders, and conversations

## Run locally with XAMPP

1. Start **MySQL** in XAMPP. Apache is not required for the PHP built-in development server below.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`, choose **Import**, and import `database/schema.sql`.
3. Confirm `config/config.php` matches your local MySQL settings. The default XAMPP setup uses user `root`, a blank password, and database `balangkare_market`.
4. Open PowerShell in this project folder and run:

   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t .
   ```

5. Visit `http://localhost:8000`.

For an existing database from before messaging was added, import `database/add-messages-table.sql` first if it has no `messages` table, then import `database/migrate-product-conversations.sql` once. If the database already has the earlier order-linked `messages` table, import only `database/migrate-product-conversations.sql`. This moves existing messages into product conversations. Back up the database before running the migration.

For an existing database created before profile editing was added, import `database/add-profile-fields.sql` once to add the address and profile photo fields.

This starts PHP from the project folder while XAMPP supplies MySQL. The app uses root-relative URLs, so keep the PHP server's document root at this project folder.

## First walkthrough

1. Register a Farmer account and publish a sample product.
2. Open a private/incognito browser window and register a Buyer account with a different email.
3. Browse the market and choose **Message** on a product card to ask the farmer a question.
4. Log in as the farmer, open Buyer messages, and reply. Both participants can continue the chat without refreshing.
5. Send an order request when ready; the farmer can then accept, reject, or update its status.

Use made-up data for demos. Order requests do not take payment. The buyer and farmer agree on payment and pickup/delivery outside the app.

## Farm Guide status

The chat page currently uses simple local PHP rules. It is a **demo guide, not an AI integration** and does not have live local weather, crop prices, soil data, or current planting advisories. A real AI provider can be connected later through a server-side API key stored privately; never put an API key in browser JavaScript or commit it to GitHub. Confirm agronomy guidance with local agricultural experts.

## Data and security notes

- Passwords are stored as password hashes.
- Database actions use prepared PDO statements, and forms use CSRF tokens.
- `config/config.php` contains local database settings and is ignored by Git. Keep real credentials private.
- This is a capstone starter, not a production-ready commerce service. Add HTTPS, email verification, password reset, rate limiting, backups, privacy/consent text, and stronger deployment configuration before serving real users.

## Current feature set

- Farmer and buyer registration/login
- Profile updates for name, email, phone, private address, and profile picture
- Searchable product marketplace with category and maximum-price filters
- Farmer product creation, optional product photos, and pause/activate controls
- Buyer order requests with quantity checks and stock reservation
- Farmer order acceptance/rejection/ready/completed statuses
- Buyer order history and cancellation of new requests
- Product conversations started by buyers from marketplace cards and answered from the farmer inbox, with automatic updates every three seconds
- Responsive Farm Guide demo
