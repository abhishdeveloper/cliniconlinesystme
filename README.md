# Clinic Management System

A comprehensive web-based application for managing clinic operations, including patient appointments, doctor schedules, and prescriptions.

## Features

### Admin
- **Dashboard**: Overview of clinic statistics.
- **Manage Users**: Control user roles and access.
- **Medicine Inventory**: Manage medicines and stock.

### Doctor
- **Dashboard**: View daily schedule and appointments.
- **Schedule Management**: Set availability and working hours.
- **Prescriptions**: Create and manage patient prescriptions digitally.
- **Patient History**: View patient appointment history.

### Patient
- **Registration & Login**: Secure account creation.
- **Book Appointments**: View available slots and book appointments.
- **Dashboard**: Manage upcoming and past appointments.
- **Profile**: Update personal details.

### General
- **Authentication**: Secure login and role-based access control (RBAC).
- **Responsive Design**: Built with Bootstrap 5 for mobile and desktop compatibility.
- **Flash Messages**: User-friendly notifications for actions.

## Tech Stack

- **Backend**: PHP (Native)
- **Database**: SQLite (Default) / MySQL
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Icons**: FontAwesome

## Prerequisites

- **PHP**: Version 7.4 or higher
- **Web Server**: Apache, Nginx, or PHP built-in server
- **PDO Extension**: Enabled for SQLite/MySQL

## Installation

### 1. Clone the Repository
```bash
git clone https://github.com/abhishdeveloper/clinic-management-system.git
cd clinic-management-system
```

### 2. Configure Database
The application is configured to use **SQLite** by default for easy setup. It will automatically create a `clinic.db` file in the root directory upon the first run.

**To use MySQL:**
1. Open `config/database.php`.
2. Set `$use_sqlite = false;`.
3. Update `$host`, `$dbname`, `$username`, and `$password` variables with your MySQL credentials.
4. Import the provided SQL dump (if available) or rely on the script to create tables (limited support in `config/database.php` for MySQL auto-creation, manual import recommended for production).

### 3. Run the Application
You can use the built-in PHP server for development:

```bash
php -S localhost:8000
```

Open your browser and navigate to `http://localhost:8000`.

## Default Credentials

The system automatically seeds the database with the following accounts on the first run:

| Role    | Email                | Password    |
|---------|----------------------|-------------|
| Admin   | `admin@clinic.com`   | `admin123`  |
| Doctor  | `doctor@clinic.com`  | `doctor123` |
| Patient | `patient@clinic.com` | `patient123`|

## Project Structure

```
├── admin/          # Admin functionality (dashboard, medicines, etc.)
├── assets/         # CSS, JS, Images
├── config/         # Database configuration
├── doctor/         # Doctor functionality (dashboard, schedules, etc.)
├── includes/       # Shared partials (header, footer, functions)
├── patient/        # Patient functionality (dashboard, appointments)
├── tests/          # Testing scripts
├── index.php       # Landing page
├── migration.php   # Database migration utility
└── ...
```

## Testing

To verify the installation and database schema, you can run the provided test script:

```bash
php tests/test_new_features.php
```

## Updates & Migrations

If new database columns or tables are added in future updates, run the migration script to update your existing database:

```bash
php migration.php
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Credits

Copyright (c) 2026 [abhishdeveloper](https://github.com/abhishdeveloper)
