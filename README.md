# Kraved — Homemade Cookie E-Commerce

Custom PHP 8.2+ MVC storefront for a single-vendor UK-style dessert ordering site.

## Requirements

- PHP 8.2+ with PDO MySQL
- MySQL 8 / MariaDB 10.5+
- Apache with `mod_rewrite` (or nginx equivalent)

## Setup

1. Import the database:

```bash
mysql -u root -p < database/schema.sql
```

2. Edit `config/database.php` with your DB credentials.

3. Edit `config/app.php` and set `url` to your public document root, e.g.:

```php
'url' => 'http://localhost/Kraved/public',
```

Or with PHP's built-in server from the `public` folder:

```bash
cd public
php -S localhost:8080
```

Then set `'url' => 'http://localhost:8080'`.

4. Point the web server document root to `/public`.

## Default admin

- URL: `/admin/login`
- Email: `admin@kraved.local`
- Password: `password`

Change this immediately after first login.

## Features

- Collection / Delivery fulfillment bar with postcode checker
- Sticky category menu, product grid, customisation modal (add-ons + box deals)
- AJAX cart drawer with free-delivery meter
- Guest / registered checkout (COD + card stub)
- Live order status tracker
- Admin dashboard, products, categories, add-ons, delivery zones, order board with poll notifications & thermal receipt
