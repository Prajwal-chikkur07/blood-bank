# Blood Bank & Donor Management System

A PHP/MySQL web application for managing blood donors, inventory, broadcasts, and notifications.

## Tech Stack

- PHP 8+ (built-in dev server or Apache/XAMPP/MAMP)
- MySQL (database: `bloodbank`)
- HTML/CSS/JavaScript (vanilla, no frameworks)
- PHPMailer + Brevo API (email)
- Fast2SMS / UltraMsg WhatsApp API (SMS)

## Project Structure

```
Blood_Bank_Project/
├── index.php               # Public home page
├── login.php               # Admin login / logout
├── database.sql            # DB schema + seed data
├── start_server.bat        # Windows quick-start (XAMPP)
├── admin/
│   ├── dashboard.php       # Admin overview + donor actions
│   ├── manage_donors.php   # Full donor CRUD + filter
│   ├── edit_donor.php      # Edit individual donor
│   ├── broadcast.php       # SMS broadcast to donors
│   ├── email_donors.php    # Send individual emails
│   ├── blood_stock.php     # Blood inventory by group
│   └── send_sms.php        # SMS endpoint (JSON)
├── donor/
│   └── register.php        # Public donor registration form
├── config/
│   ├── db.php              # MySQL connection
│   ├── email_config.php    # Brevo API key + sender
│   └── sms_config.php      # Fast2SMS / WhatsApp config
├── includes/
│   ├── email_sender.php    # sendEmail() + buildEmailTemplate()
│   └── sms_sender.php      # sendSMS() / sendWhatsApp() / sendFast2SMS()
├── assets/
│   ├── css/style.css
│   ├── js/main.js
│   └── images/
└── logs/
    ├── sms_log.txt         # SMS + email activity log
    └── whatsapp_log.txt    # WhatsApp activity log
```

## Database

Three tables in the `bloodbank` database:

- `admin` — admin credentials (default: `admin` / `admin123`)
- `donors` — donor records with status: Pending, Approved, Rejected, Donated
- `broadcasts` — history of SMS broadcasts sent

## Setup & Run

### macOS (Homebrew)

```bash
# Install dependencies
brew install php mysql

# Start MySQL
mysqld_safe --datadir=/opt/homebrew/var/mysql &

# Import database
mysql -u root < database.sql

# Start PHP server
php -S localhost:8080 -t .
```

### Windows (XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/)
2. Copy project into `C:\xampp\htdocs\Blood_Bank_Project\`
3. Start Apache + MySQL from XAMPP Control Panel
4. Import `database.sql` via phpMyAdmin
5. Run `start_server.bat` or visit `http://localhost/Blood_Bank_Project/`

### MAMP (macOS)

1. Install [MAMP](https://www.mamp.info/)
2. Copy project into `MAMP/htdocs/Blood_Bank_Project/`
3. Start servers from MAMP control panel
4. Import `database.sql` via phpMyAdmin at `http://localhost:8888/phpmyadmin`
5. Visit `http://localhost:8888/Blood_Bank_Project/`

## Access

| URL | Description |
|-----|-------------|
| `http://localhost:8080/` | Public home page |
| `http://localhost:8080/login.php` | Admin login |
| `http://localhost:8080/donor/register.php` | Donor registration |
| `http://localhost:8080/admin/dashboard.php` | Admin dashboard (auth required) |

Default admin credentials: `admin` / `admin123`

## Configuration

### Email (`config/email_config.php`)

Uses [Brevo](https://www.brevo.com/) transactional email API.

```php
define('EMAIL_ENABLED', true);
define('BREVO_API_KEY', 'your-api-key');
define('EMAIL_FROM_ADDRESS', 'you@example.com');
define('EMAIL_FROM_NAME', 'Blood Bank');
```

### SMS (`config/sms_config.php`)

```php
define('SMS_TEST_MODE', true);       // true = log only, no real SMS
define('SMS_GATEWAY', 'fast2sms');   // 'fast2sms' or 'whatsapp'
define('FAST2SMS_API_KEY', 'your-key');
define('WHATSAPP_INSTANCE_ID', 'your-instance');
define('WHATSAPP_TOKEN', 'your-token');
```

Set `SMS_TEST_MODE` to `false` to send real messages.

## Features

- Donor registration with email + SMS confirmation
- Admin approval / rejection / donation confirmation workflow
- SMS broadcast to all donors or by blood group
- Individual email composer per donor
- Blood inventory dashboard with low-stock alerts
- Broadcast history log
- WhatsApp deep-link messaging from dashboard
