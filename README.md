# PHP User Management Portal

A modern, responsive User Management & Authentication Portal built with **PHP 8** and **MySQL / MariaDB** styled with **Bootstrap 5.3** and **Bootstrap Icons**.

---

## 🚀 How to Launch `index.php`

### Option 1: One-Click Quick Launch (Recommended on Windows)
Simply double-click:
```
start_server.bat
```
*(Or in PowerShell: `.\launch.ps1`)*

This script automatically detects your PHP installation (including XAMPP at `C:\xampp\php\php.exe`), starts the built-in server on `http://localhost:8000`, and opens it in your default web browser!

---

### Option 2: Command Line (PHP Built-in Server)

1. Open PowerShell or Command Prompt.
2. Navigate to this project folder:
   ```powershell
   cd C:\xampp\htdocs\site
   ```
3. Run the PHP built-in web server (if not using Apache):
   - **If PHP is in your system PATH:**
     ```bash
     php -S localhost:8000
     ```
   - **If using XAMPP PHP directly:**
     ```powershell
     & "C:\xampp\php\php.exe" -S localhost:8000
     ```
4. Open your browser and go to:
   ```
   http://localhost:8000
   ```

---

### Option 3: Direct Access via XAMPP Apache Server (Active)

Since this project is located in `C:\xampp\htdocs\site`:
1. Ensure **Apache** and **MySQL** are running in the **XAMPP Control Panel**.
2. Open your web browser and go directly to:
   ```
   http://localhost/site/index.php
   ```

---

## 🗄️ Database Setup

Ensure MySQL is running in your **XAMPP Control Panel**.

### Automatic 1-Click Database & Table Setup:
- When you first visit `index.php`, if the `sample` database or `users` table is missing, the portal will display an **Auto-Create / Initialize Table** button. Simply click it to set everything up automatically!

### Manual Database Import (Optional):
1. Open [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Create a new database named `sample`.
3. Select `sample` and click the **Import** tab.
4. Choose the `setup.sql` file located in this directory and click **Import**.

---

## 📁 Project Structure

| File | Purpose |
|---|---|
| `index.php` | Main front-end dashboard with KPI metrics, user registration, live search, view modal, and edit modal |
| `create.php` | Handles new user registration, email uniqueness validation, and secure password hashing (`PASSWORD_DEFAULT`) |
| `update.php` | Handles user edits (name, email) and optional password resets |
| `delete.php` | Handles user record deletion with confirmation |
| `export.php` | Exports all registered users to a downloadable `.csv` file |
| `seed.php` | Populates the database with demo users for testing |
| `db.php` | Database connection handler with auto-recovery and diagnostic error page |
| `setup.sql` | SQL schema script for the `sample` database and `users` table |
| `start_server.bat` | One-click Windows batch script to launch the server and browser |
| `launch.ps1` | PowerShell launcher script |

---

## ✨ Features Included

- **Full CRUD Operations**: Create, Read, Update (with optional password change), and Delete records.
- **Modern UI & UX**: Designed with Bootstrap 5.3, Bootstrap Icons, responsive layout, and avatar chips.
- **Client-Side Live Filter**: Instant search across names, emails, user IDs, and password hashes.
- **Security**:
  - Prepared statements with parameterized queries (`mysqli::prepare`) to prevent SQL Injection.
  - Secure bcrypt password hashing via `password_hash()` and `PASSWORD_DEFAULT`.
  - Full output escaping with `htmlspecialchars()` to prevent XSS.
- **Quick Actions Menu**:
  - 1-Click Seed Demo Data
  - Export registered users to CSV
  - 1-Click Table Re-initialization & Clear Table
