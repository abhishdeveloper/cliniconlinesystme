# Clinic Management System

A comprehensive, web-based Clinic Management System designed for Ayurveda clinics. This application facilitates patient registration, appointment booking, doctor management, prescription generation, video consultations, and real-time messaging.

## Features

*   **User Roles:** Super Admin, Admin, Doctor, Patient.
*   **Appointment Management:** Book, reschedule, and cancel appointments.
*   **Video Consultation:** Integrated Jitsi Meet for secure video calls with real-time captioning.
*   **Prescriptions:** Digital prescription generation with support for Ayurvedic diagnosis (Prakruti, Vikruti) and medicines.
*   **Messaging:** Internal chat system between doctors and patients.
*   **Reports:** Patient document/report upload and management.
*   **Security:** CSRF protection, secure sessions, XSS prevention, and hashed passwords.
*   **Responsive Design:** Mobile-friendly interface using Bootstrap 5.

## Prerequisites

*   **PHP:** 7.4 or higher.
*   **Database:** MySQL 5.7+ or MariaDB (Production), SQLite (Development).
*   **Web Server:** Apache (with `mod_rewrite`) or Nginx.
*   **Extensions:** `pdo_mysql`, `curl`, `mbstring`, `json`.

## Installation

### 1. Shared Hosting (MySQL)

1.  **Upload Files:**
    *   Upload all files to your web server (e.g., `public_html` or a subdirectory).
    *   Ensure the `uploads/` directory is writable (`chmod 755` or `777`).

2.  **Database Setup:**
    *   Create a new MySQL database and user via your hosting control panel (cPanel, etc.).
    *   Import the `database.sql` file into your new database using phpMyAdmin.
    *   *Alternatively*, you can run `setup_database.php` in your browser (if configured) to initialize tables, but importing `database.sql` is recommended.

3.  **Configuration:**
    *   Rename `.env.example` to `.env` (if it exists) or create a new `.env` file in the root directory.
    *   Add your database credentials:
        ```ini
        DB_CONNECTION=mysql
        DB_HOST=localhost
        DB_NAME=your_database_name
        DB_USER=your_database_user
        DB_PASS=your_database_password
        ```
    *   *Note:* If you cannot create a `.env` file, you can edit `config/database.php` directly (fallback section), but using environment variables is more secure.

4.  **Access the Application:**
    *   Navigate to your website URL (e.g., `https://your-clinic.com`).

### 2. Local Development (SQLite)

1.  **Clone the Repository:**
    ```bash
    git clone <repository_url>
    cd <repository_folder>
    ```

2.  **Start Server:**
    *   You can use the built-in PHP server:
        ```bash
        php -S localhost:8000
        ```
    *   The application detects the environment. To force SQLite, set `DB_CONNECTION=sqlite` in your `.env` file or environment.

## Default Credentials

**Super Admin:**
*   Email: `superadmin@clinic.com`
*   Password: `admin123`

**Admin:**
*   Email: `admin@clinic.com`
*   Password: `admin123`

**Doctor:**
*   Email: `doctor@clinic.com`
*   Password: `doctor123`

**Patient:**
*   Email: `patient@clinic.com`
*   Password: `patient123`

*> **Important:** Change these passwords immediately after installation.*

## Folder Structure

*   `admin/` - Admin dashboard and management scripts.
*   `doctor/` - Doctor dashboard, schedule, and prescription management.
*   `patient/` - Patient dashboard, booking, and history.
*   `includes/` - Core functions, authentication, and header/footer.
*   `config/` - Database configuration.
*   `uploads/` - Stored patient reports and documents.
*   `tests/` - Automated test scripts.

## License

This project is open-source. Please check the `LICENSE` file for details.
