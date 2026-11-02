# RP-Ngoma College Attendance Portal (PHP + MySQL)

Clean URLs, no `.php` in the address bar:

| Page | URL |
|---|---|
| Login | `/` or `/login` |
| Trainer | `/trainer/` |
| Director of QA | `/director/` |
| Student | `/student/` |
| Manage modules (Director) | `/director/modules` |
| Log out | `/logout` |

The project works on a domain root (`https://portal.example.rw/`) or in a subfolder (`http://localhost/rp-ngoma/`); the base path is detected automatically.

## A. Run locally (XAMPP / WAMP / Laragon)
1. Copy the `rp-ngoma` folder into `htdocs` (XAMPP) or `www` (WAMP).
2. Start Apache and MySQL. In phpMyAdmin choose Import and import `database.sql`.
3. `config.php` defaults (root, empty password) work on XAMPP.
4. Open http://localhost/rp-ngoma/  (set `DEBUG = true` in config.php while developing).

## B. Host it online (cPanel or any shared hosting)
1. In cPanel > MySQL Databases: create a database, create a user, and add the user to the database with All Privileges.
2. In phpMyAdmin select that new database and import `database_hosting.sql` (not `database.sql`).
3. Edit `config.php`: set DB_NAME, DB_USER, DB_PASS (cPanel names usually look like `account_dbname`). Keep DEBUG = false.
4. Upload everything (including the hidden `.htaccess` files) to `public_html` (or a subdomain folder). Use PHP 8.0 or newer.
5. Open your domain. The login page appears at `/` and the dashboards at `/trainer/`, `/director/`, `/student/`.
6. Turn on HTTPS in your host, then remove the `#` in front of the HTTPS lines in `.htaccess` to force it.

If clean URLs give a 404 on `/logout`, ask the host to enable `mod_rewrite` and `AllowOverride All`.
On Nginx, add: `location / { try_files $uri $uri/ $uri.php?$query_string; }` and pass `.php` to PHP-FPM.

## Demo logins (password 1234)
trainer1, trainer2 | student1 (22RP001), student2 (22RP002) | qa (Director of Quality Assurance)
Change these passwords before going live.

## Modules
The Director adds modules (code + name) at `/director/modules` and assigns each to a trainer. A trainer can only upload lists whose `module` column matches (by name or code) a module assigned to them; otherwise the whole upload is rejected and nothing is saved. A module with attendance records cannot be deleted, and renaming a module also renames its existing records.

## How it is used
0. Director: check /director/modules (demo modules are already assigned).
1. Trainer uploads `sample_attendance.csv` (or an .xlsx with the same columns).
2. Director opens the list and clicks Confirm received. An SMS is created for the trainer.
3. Student sees marks out of 100 per module, with a check for at least 2 modules.

## Real SMS
Set SMS_USERNAME, SMS_API_KEY and SMS_URL in `config.php` (Africa's Talking). Phone numbers use international format (+2507...). With no key, messages are only saved in `sms_log`.

## Security built in
Hashed passwords, prepared SQL statements, CSRF tokens on forms, escaped output, role checks on every page, HttpOnly session cookies, blocked access to config.php and .sql files, no directory listing.

## Add a user
INSERT into `users` with a hash made by `password_hash('newpassword', PASSWORD_DEFAULT)` in PHP.
