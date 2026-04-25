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
 Version: 2.0 | Built with PHP + Bootstrap 5 + MySQL
================================================================

👨‍💻 Author
Aditya Chaturvedi
