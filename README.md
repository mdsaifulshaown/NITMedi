NITMedi – Medical Center Management System

Overview

NITMedi is a full-stack Medical Center Management System developed for NIT Meghalaya.
It enables administrators and consultants (doctors/nurses) to manage patient records, consultations, and medicines in a secure, professional, and efficient way.
The project is built using:

Frontend: HTML, CSS, JavaScript
Backend: PHP (with PDO for database interactions)
Database: MySQL


Features

User Roles

Admin

  Manage students, teachers, and staff (add, update, delete).
  Manage medicine inventory (add, update, delete).
  View consultation records.

Consultant (Doctor/Nurse)

  Manage consultations with patients.
  Add consultation notes, diagnosis, and prescribed medicines.
  Automatically calculate medicine totals.


Database Design

Core Tables

1. users

   Stores login credentials and roles (Admin/Consultant).

2. patients

   Stores student, teacher, and staff details.

3. consultations

   Stores consultation records linked to patients and consultants.

4. medicines

   Stores available medicines with price and stock information.

5. medicines

   Links consultations with prescribed medicines and quantities.


Installation

Prerequisites

PHP >= 8.0
MySQL >= 5.7
Apache/Nginx server (e.g., XAMPP, Laragon, WAMP)
Git installed

Steps

1. Clone the repository:

   bash
   git clone https://github.com/your-username/NITMedi.git
   cd NITMedi
   

2. Copy the example config and set up your database credentials:

   bash
   cp db/config.example.php db/config.php
   

   Edit `config.php` with your MySQL username, password, and database name.

3. Import the database schema (SQL file provided in `/db/schema.sql`) into MySQL.

4. Start your local server (e.g., with Laragon/XAMPP) and open:


   http://localhost/medical_center/



Project Structure

NITMedi/
│
├── admin/                  # Admin panel pages
├── consultant/             # Consultant dashboard
├── db/                     # Database configuration and schema
│   ├── config.php          # Your actual config (not pushed to GitHub)
│   └── config.example.php  # Example config (safe to push)
├── includes/               # Shared functions
├── index.php               # Landing page with role selection
├── dashboard.php           # Consultant/Admin dashboard
└── README.md               # Project documentation




Security Notes

Do not push `db/config.php` to GitHub. Only `config.example.php` should be included.
Passwords should always be hashed (e.g., using `password_hash()`).
Use prepared statements (PDO) for all database queries to prevent SQL injection.


Contribution Guidelines

1. Fork the repository.
2. Create a new branch for your feature/fix:

   bash
   git checkout -b feature-name
   
3. Commit your changes with clear messages.
4. Push to your branch and create a pull request.


License

This project is licensed under the MIT License.



