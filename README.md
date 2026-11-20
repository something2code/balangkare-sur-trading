# Balangkare Sur Trading

A starter PHP/MySQL marketplace for the capstone proposal: local farmers can list harvests, buyers can request quantities, and both sides can coordinate payment and fulfillment directly.

## Stack

- HTML and CSS for accessible, responsive pages
- JavaScript for the mobile menu and Farm Guide chat interface
- PHP 8+ for sessions, validation, marketplace, and order handling
- MySQL/MariaDB for accounts, products, and orders

## Run locally with XAMPP

1. Start **MySQL** in XAMPP. Apache is not required for the PHP built-in development server below.
2. Open phpMyAdmin at `http://localhost/phpmyadmin`, choose **Import**, and import `database/schema.sql`.
3. Confirm `config/config.php` matches your local MySQL settings. The default XAMPP setup uses user `root`, a blank password, and database `balangkare_market`.
4. Open PowerShell in this project folder and run:

   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t .
   ```

5. Visit `http://localhost:8000`.

This starts PHP from the project folder while XAMPP supplies MySQL. The app uses root-relative URLs, so keep the PHP server's document root at this project folder.

## First walkthrough

1. Register a Farmer account and publish a sample product.
2. Open a private/incognito browser window and register a Buyer account with a different email.
3. Browse the market and send an order request.
4. Log in as the farmer to accept, reject, or update the order status.
5. Log in as the buyer to view the order and farmer's contact phone.

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
- Searchable product marketplace with category and maximum-price filters
- Farmer product creation, optional product photos, and pause/activate controls
- Buyer order requests with quantity checks and stock reservation
- Farmer order acceptance/rejection/ready/completed statuses
- Buyer order history and cancellation of new requests
- Responsive Farm Guide demo
