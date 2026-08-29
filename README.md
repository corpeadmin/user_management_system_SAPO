# PHP User Management Portal

>  **[System Demo Screenshots](./images/system_demo.png)**

A modern, responsive User Management & Authentication Portal built with **PHP 8** and **MySQL / MariaDB**, styled with **Bootstrap 5.3** and **Bootstrap Icons**.

---

## 🚀 How to Launch `index.php`

### Option 1: One-Click Quick Launch (Recommended on Windows)

Simply double-click:

```text
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

3. Run the PHP built-in web server:

**If PHP is in your system PATH:**

```bash
php -S localhost:8000
```

**If using XAMPP PHP directly:**

```powershell
& "C:\xampp\php\php.exe" -S localhost:8000
```

4. Open your browser:

```text
http://localhost:8000
```

---

### Option 3: Direct Access via XAMPP Apache Server

Since this project is located in `C:\xampp\htdocs\site\`:

1. Ensure **Apache** and **MySQL** are running in the XAMPP Control Panel.
2. Open:

```text
http://localhost/site/index.php
```

---

## 🗄️ Database Setup

Ensure **MySQL/MariaDB** is running in your **XAMPP Control Panel**.

### Automatic 1-Click Database & Table Setup

When you first visit `index.php`, if the `sample` database or `users` table is missing, the portal will display an **Auto-Create / Initialize Table** button.

Simply click it to initialize the required database structure.

### Manual Database Import

1. Open `http://localhost/phpmyadmin/`.
2. Create a database named:

```text
sample
```

3. Select `sample`.
4. Click the **Import** tab.
5. Select the `setup.sql` file.
6. Click **Import**.

---

## 📊 Database Schema

The application uses a `users` table in the `sample` database.

```text
MariaDB [sample]> DESC users;

+------------+--------------+------+-----+---------------------+----------------+
| Field      | Type         | Null | Key | Default             | Extra          |
+------------+--------------+------+-----+---------------------+----------------+
| id         | int(11)      | NO   | PRI | NULL                | auto_increment |
| name       | varchar(100) | YES  |     | NULL                |                |
| email      | varchar(100) | YES  |     | NULL                |                |
| password   | varchar(255) | YES  |     | NULL                |                |
| created_at | timestamp    | NO   |     | current_timestamp() |                |
+------------+--------------+------+-----+---------------------+----------------+
```

### Schema Description

| Column       | Description                                  |
| ------------ | -------------------------------------------- |
| `id`         | Primary key with automatic increment         |
| `name`       | User's name                                  |
| `email`      | User's email address                         |
| `password`   | Securely hashed password                     |
| `created_at` | Automatically records the creation timestamp |

---

## 🔄 Full CRUD Operations

The portal implements complete **Create, Read, Update, and Delete** functionality.

### Create

Handled by:

```text
create.php
```

Creates a new user after validating the submitted information.

The email address must be unique, and passwords are securely hashed before being stored.

### Read

Handled primarily by:

```text
index.php
```

The dashboard retrieves and displays registered users along with KPI metrics.

Users can be searched and viewed through the interface.

### Update

Handled by:

```text
update.php
```

Allows administrators to update:

* Name
* Email
* Password (optional)

If no new password is supplied, the existing password remains unchanged.

### Delete

Handled by:

```text
delete.php
```

Deletes a selected user record after confirmation.

---

# 📡 JSON Contract

The system's user data can be represented using the following JSON structure.

## User Object

```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "password": "$2y$10$example_hash",
  "created_at": "2026-08-29 10:30:00"
}
```

> **Security note:** Password hashes should not normally be exposed to frontend clients or API responses. The `password` field is shown here only to document the underlying database representation.

### Create User Request

```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123"
}
```

### Create User Response

```json
{
  "success": true,
  "message": "User created successfully",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "created_at": "2026-08-29 10:30:00"
  }
}
```

### Update User Request

```json
{
  "name": "John Updated",
  "email": "john.updated@example.com",
  "password": "newpassword123"
}
```

### Update User Response

```json
{
  "success": true,
  "message": "User updated successfully",
  "user": {
    "id": 1,
    "name": "John Updated",
    "email": "john.updated@example.com",
    "created_at": "2026-08-29 10:30:00"
  }
}
```

### Delete Response

```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

### Error Response

```json
{
  "success": false,
  "message": "An error occurred"
}
```

---

## 📁 Project Structure

| File                  | Purpose                                                                                     |
| --------------------- | ------------------------------------------------------------------------------------------- |
| `index.php`           | Main dashboard with KPI metrics, user registration, live search, view modal, and edit modal |
| `create.php`          | Handles new user registration, email uniqueness validation, and secure password hashing     |
| `update.php`          | Handles user edits and optional password resets                                             |
| `delete.php`          | Handles user record deletion with confirmation                                              |
| `export.php`          | Exports all registered users to a downloadable `.csv` file                                  |
| `seed.php`            | Populates the database with demo users for testing                                          |
| `db.php`              | Database connection handler with auto-recovery and diagnostic error page                    |
| `setup.sql`           | SQL schema script for the `sample` database and `users` table                               |
| `start_server.bat`    | One-click Windows batch script to launch the server and browser                             |
| `launch.ps1`          | PowerShell launcher script                                                                  |
| `images/system_demo/` | Screenshots and visual demonstrations of the system                                         |

---

## ✨ Features Included

### Full CRUD Operations

* **Create** user records
* **Read** and display user records
* **Update** existing users
* **Delete** user records
* Optional password changes during updates

### Modern UI & UX

* Bootstrap 5.3 interface
* Bootstrap Icons
* Responsive layout
* Avatar chips
* KPI dashboard metrics
* View-user modal
* Edit-user modal
* Confirmation dialogs

### Client-Side Live Filter

Instantly filters users based on:

* Names
* Email addresses
* User IDs
* Password hashes

### Security

* Prepared statements using `mysqli::prepare()`
* Parameterized database queries
* Protection against SQL injection
* Secure password hashing using `password_hash()`
* `PASSWORD_DEFAULT` hashing algorithm
* HTML output escaping with `htmlspecialchars()`
* Email uniqueness validation

### Quick Actions

The dashboard provides quick access to:

* **Seed Demo Data**
* **Export Users to CSV**
* **Re-initialize Table**
* **Clear Table**

---

## 📤 CSV Export

The `export.php` endpoint allows registered users to be exported as a downloadable CSV file.

Exported information is generated directly from the `users` database table.

---

## 🌱 Demo Data

The `seed.php` script can be used to populate the database with demonstration users.

This is useful for:

* Testing CRUD operations
* Demonstrating the dashboard
* Testing the live search
* Testing CSV exports
* Testing update and delete functionality

---

## 🛠️ Technology Stack

| Technology          | Purpose                                     |
| ------------------- | ------------------------------------------- |
| **PHP 8**           | Backend/server-side application             |
| **MySQL / MariaDB** | Relational database                         |
| **XAMPP**           | Local development environment               |
| **Bootstrap 5.3**   | UI framework                                |
| **Bootstrap Icons** | Interface icons                             |
| **HTML5 / CSS3**    | Frontend structure and styling              |
| **JavaScript**      | Client-side interactions and live filtering |
| **JSON**            | Data contract / structured data format      |

---

## 🖼️ System Demo

Screenshots and demonstrations of the completed application are available in:

**[View `images/system_demo`](./images/system_demo)**

The folder contains the visual documentation of the User Management Portal, including its dashboard and CRUD functionality.

---

## 📌 Summary

The PHP User Management Portal is a complete local CRUD application that combines a responsive Bootstrap interface with PHP 8 and a MySQL/MariaDB database running through XAMPP.

The application provides user creation, retrieval, modification, deletion, demo-data seeding, CSV export, database initialization, live filtering, and basic security protections against common web application vulnerabilities.
