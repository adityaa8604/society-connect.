# 🏠 Society Connect v2.0

**Society Connect** is a web-based society management system designed to simplify and organize daily operations in residential societies. The system provides separate modules for Admin, Residents, and Staff to ensure smooth and efficient management.

---

## 🚀 Project Overview

This application helps manage all essential society activities in one place, including residents, flats, billing, complaints, visitors, and facility bookings. It is built with simplicity and usability in mind.

---

## 🛠️ Tech Stack

* **Frontend:** HTML5, Bootstrap 5, JavaScript, Font Awesome
* **Backend:** Core PHP (PDO)
* **Database:** MySQL
* **Server:** Apache (XAMPP compatible)

---

## 📁 Folder Structure

```
society-connect/
│
├── admin/        (Admin module)
├── user/         (Resident module)
├── staff/        (Staff module)
│
├── config/       (Database configuration)
├── assets/       (CSS & JS files)
├── includes/     (Header, footer, sidebar)
├── database/     (SQL file)
│
├── login.php
├── register.php
├── logout.php
├── index.php
└── README.md
```

---

## 👨‍💼 Admin Features

* Dashboard with overall statistics
* Manage residents and flat assignments
* Staff and vendor management
* Generate monthly maintenance bills
* Track payments
* Assign and monitor complaints
* Manage visitor entries
* Approve or reject facility bookings
* Post and manage notices

---

## 👨‍👩‍👧 Resident Features

* Personal dashboard
* View and pay maintenance bills
* Raise and track complaints
* Book society facilities
* Pre-approve visitors
* View notices
* Update profile

---

## 🛠️ Staff Features

* Dashboard with assigned tasks
* Update complaint status
* Manage visitor entries

---

## ✨ Key Highlights

* Role-based authentication system
* Clean and responsive UI
* Secure backend using PDO
* Well-structured and readable code
* Beginner-friendly implementation

---

## ⚙️ Setup Instructions

1. Install XAMPP and start Apache & MySQL
2. Copy the project folder into `htdocs`
3. Open phpMyAdmin and create a new database
4. Import the file from `database/society.sql`
5. Update database credentials in `config/db.php`
6. Open your browser and run:

   ```
   http://localhost/society-connect
   ```

---

## 📌 Notes

* Use PHP version 7 or above
* Make sure MySQL service is running

---

## 👨‍💻 Author

Aditya Chaturvedi

