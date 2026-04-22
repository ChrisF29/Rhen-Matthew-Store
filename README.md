# Rhen Matthew Store - Inventory Management System

Web-based Inventory Management System (IMS) scaffold for a softdrinks supplier, built with PHP, MySQL, Vanilla JS, and Lucide Icons.

## Features Included

- Session-based authentication (login, register, logout)
- Modular dashboard layout (sidebar + top navbar)
- Core modules: Dashboard, Products, Inventory, Sales, Ongoing Deliveries, Customers, Deliveries, Drivers, Users
- Reusable PHP includes for shared UI and configuration
- API-first CRUD endpoints using prepared statements
- Transaction-safe stock updates on inventory and sales operations
- Delivery-first workflow for partial drops/backloads (dispatch then finalize delivered quantities into sales)
- Responsive modern UI with external CSS and Vanilla JS

## Tech Stack

- Frontend: HTML5, CSS3, Vanilla JavaScript, Lucide Icons
- Backend: Core PHP (MVC-inspired modular structure)
- Database: MySQL (via phpMyAdmin)

## Folder Structure

```text
/Rhen-Matthew-Store/
|-- assets/
|   |-- css/style.css
|   |-- js/app.js
|   |-- icons/
|   `-- images/
|
|-- includes/
|   |-- config.php
|   |-- header.php
|   |-- sidebar.php
|   `-- footer.php
|
|-- modules/
|   |-- dashboard/index.php
|   |-- products/index.php
|   |-- inventory/index.php
|   |-- sales/index.php
|   |-- ongoing_deliveries/index.php
|   |-- customers/index.php
|   |-- deliveries/index.php
|   |-- drivers/index.php
|   `-- users/index.php
|
|-- api/
|   |-- product_api.php
|   |-- inventory_api.php
|   |-- sales_api.php
|   |-- ongoing_delivery_api.php
|   |-- customer_api.php
|   |-- user_api.php
|   |-- driver_api.php
|   `-- delivery_api.php
|
|-- database/
|   `-- schema.sql
|
|-- index.php
|-- login.php
|-- register.php
`-- logout.php
```

## Setup Guide (XAMPP + phpMyAdmin)

1. Place this project in your XAMPP htdocs folder.
2. Start Apache and MySQL in XAMPP Control Panel.
3. Open phpMyAdmin and import `database/schema.sql`.
4. Confirm DB credentials in `includes/config.php`:
	- Host: `127.0.0.1`
	- Database: `rhen_matthew_store`
	- User: `root`
	- Password: empty by default in XAMPP
5. Open the app at:
	- `http://localhost/Rhen-Matthew-Store/login.php`

For existing installations, run additional migration scripts as needed:

- `database/add_customers_table.sql`
- `database/add_ongoing_deliveries_workflow.sql`

## Default Admin Account

- Email: `admin@store.local`
- Password: `admin12345`

## API Endpoints (Starter)

- `GET/POST/PUT/DELETE api/product_api.php`
- `GET/POST/DELETE api/inventory_api.php`
- `GET/POST/PUT/DELETE api/sales_api.php`
- `GET/POST/PUT api/ongoing_delivery_api.php`
- `GET/POST/PUT/DELETE api/customer_api.php`
- `GET/POST/PUT/DELETE api/user_api.php`
- `GET/POST/PUT/DELETE api/driver_api.php`
- `GET/POST/PUT/DELETE api/delivery_api.php`

All API routes require authenticated session access.

## Security Practices Used

- Prepared statements via PDO
- Password hashing with bcrypt (`password_hash` / `password_verify`)
- Session-based access control (`require_login()`)
- Transaction handling for stock-critical operations

## Notes for Future Laravel Migration

- Business logic is already separated by module and API endpoints
- Data access is centralized through PDO helpers
- UI layout is componentized with reusable includes
- API shape can be migrated to Laravel controllers/resources later