================================================================
 SOCIETY CONNECT v2.0 - Society Management System
 Complete Setup Guide
================================================================

PROJECT OVERVIEW
----------------
Society Connect is a full-featured web application for managing
residential societies. It includes:
  - Role-based access (Admin / Resident / Staff)
  - Resident & Flat Management
  - Billing & Dues Tracking
  - Complaint Management with staff assignment
  - Visitor Log & Entry Management
  - Facility Booking System
  - Notice Board
  - Vendor & Staff Management

TECH STACK
----------
  Frontend : HTML5, Bootstrap 5, JavaScript, Font Awesome 6
  Backend  : PHP (Core PHP with PDO)
  Database : MySQL 5.7+ / MariaDB
  Server   : Apache (XAMPP compatible)

================================================================
 STEP-BY-STEP SETUP (XAMPP)
================================================================

STEP 1: COPY PROJECT FILES
---------------------------
Copy the entire "society-connect" folder to:
  Windows : C:\xampp\htdocs\society-connect\
  Mac/Linux: /opt/lampp/htdocs/society-connect/

STEP 2: START XAMPP
--------------------
Open XAMPP Control Panel and START:
  [✓] Apache
  [✓] MySQL

STEP 3: IMPORT DATABASE
------------------------
1. Open your browser and go to: http://localhost/phpmyadmin
2. Click "New" in the left sidebar to create a new database
   (OR use the Import tab directly — the SQL auto-creates the DB)
3. Click "Import" tab at the top
4. Click "Choose File" → select: society-connect/database/society.sql
5. Click "Go" (blue button at bottom)
6. You should see: "Import has been successfully finished"

STEP 4: VERIFY DATABASE CONFIG
--------------------------------
Open: society-connect/config/db.php

Default settings (works with XAMPP default install):
  DB_HOST = 'localhost'
  DB_NAME = 'society_connect'
  DB_USER = 'root'
  DB_PASS = ''

If your MySQL has a password, update DB_PASS accordingly.

STEP 5: RUN THE APPLICATION
-----------------------------
Open your browser and go to:
  http://localhost/society-connect/

You should see the Login page.

================================================================
 LOGIN CREDENTIALS
================================================================

ADMIN
  Email    : admin@society.com
  Password : password123
  Access   : Full system access

RESIDENT (Sample users)
  Email    : rajesh@society.com
  Password : password123
  Flat     : A-101

  Email    : priya@society.com
  Password : password123
  Flat     : A-102

STAFF
  Email    : ravi@society.com
  Password : password123
  Role     : Maintenance Supervisor

================================================================
 FOLDER STRUCTURE
================================================================

society-connect/
│
├── admin/              ← Admin module
│   ├── index.php       ← Admin Dashboard
│   ├── residents.php   ← Manage Residents
│   ├── staff.php       ← Staff Management
│   ├── vendors.php     ← Vendor Management
│   ├── flats.php       ← Flat Management
│   ├── billing.php     ← Billing & Dues
│   ├── complaints.php  ← Complaints Management
│   ├── visitors.php    ← Visitor Logs
│   ├── bookings.php    ← Facility Bookings
│   └── notices.php     ← Notice Board
│
├── user/               ← Resident module
│   ├── index.php       ← Resident Dashboard
│   ├── profile.php     ← My Profile
│   ├── billing.php     ← View & Pay Bills
│   ├── complaints.php  ← Lodge Complaints
│   ├── bookings.php    ← Book Facilities
│   ├── visitors.php    ← Visitor Pre-approval
│   └── notices.php     ← View Notices
│
├── staff/              ← Staff module
│   ├── index.php       ← Staff Dashboard
│   ├── complaints.php  ← My Tasks
│   └── visitors.php    ← Visitor Logging
│
├── config/
│   └── db.php          ← DB config + helper functions
│
├── assets/
│   ├── css/style.css   ← Main stylesheet
│   └── js/main.js      ← Main JavaScript
│
├── includes/
│   ├── header.php      ← Page header (HTML head + sidebar)
│   ├── sidebar.php     ← Sidebar navigation
│   └── footer.php      ← Page footer (scripts)
│
├── database/
│   └── society.sql     ← Full database schema + sample data
│
├── login.php           ← Login page
├── register.php        ← Registration page
├── logout.php          ← Logout
├── index.php           ← Root redirect
└── README.txt          ← This file

================================================================
 FEATURES BY ROLE
================================================================

ADMIN
  ✓ Full dashboard with stats
  ✓ Add/Remove/View residents & flat assignments
  ✓ Manage staff (assign roles, shifts, salary)
  ✓ Manage vendors and contracts
  ✓ Manage all flats and occupancy
  ✓ Auto-generate monthly maintenance bills
  ✓ Mark bills as paid
  ✓ View, assign, and update complaint status
  ✓ Log and manage visitor entries
  ✓ Approve/reject facility bookings
  ✓ Post, pin, and manage notices

RESIDENT
  ✓ Personal dashboard with flat overview
  ✓ View and pay maintenance bills online
  ✓ Lodge and track complaints
  ✓ Book society facilities
  ✓ Pre-approve visitors
  ✓ View society notices
  ✓ Edit profile & change password

STAFF
  ✓ Dashboard with assigned task summary
  ✓ View and update complaint/task status
  ✓ Log visitor entries at gate

================================================================
 TROUBLESHOOTING
================================================================

Problem : White page / PHP errors
Solution: Enable error display in php.ini or check Apache logs
          Make sure PHP 7.4+ is running

Problem : Database connection failed
Solution: 1. Check MySQL is running in XAMPP
          2. Verify database name = society_connect
          3. Check credentials in config/db.php

Problem : Page not found (404)
Solution: Make sure folder is named exactly "society-connect"
          and is inside htdocs/

Problem : Images not loading
Solution: All images use online URLs from Unsplash
          Requires internet connection for facility images

Problem : Login says "Invalid credentials"
Solution: Re-import the database.sql file fresh
          All sample passwords = password123

================================================================
 CUSTOMIZATION
================================================================

Change Society Name  : Edit APP_NAME in config/db.php
Change Colors        : Edit CSS variables in assets/css/style.css
Change DB Credentials: Edit config/db.php
Add More Facilities  : INSERT into facilities table in phpMyAdmin

================================================================
 REQUIREMENTS
================================================================

  PHP        : 7.4 or higher (8.x recommended)
  MySQL      : 5.7+ or MariaDB 10.3+
  Apache     : 2.4+
  Browser    : Any modern browser (Chrome, Firefox, Edge, Safari)
  Internet   : Required for CDN (Bootstrap, FontAwesome, Fonts)
               and facility images (Unsplash)

================================================================
 SUPPORT
================================================================

This project was built as a complete, production-ready
Society Management System. All pages are tested and working.

================================================================
 Version: 2.0 | Built with PHP + Bootstrap 5 + MySQL
================================================================
