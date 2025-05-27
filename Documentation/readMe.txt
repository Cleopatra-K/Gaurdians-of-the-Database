===============================================
Price Comparison Tool - Installation Guide
===============================================

This guide will help you set up and run the Price Comparison Tool locally using XAMPP.

1. SYSTEM REQUIREMENTS
----------------------
- XAMPP (Apache + MySQL + PHP)
- Web browser (Chrome/Firefox recommended)
- Git (optional)

2. INSTALLATION STEPS
---------------------

2.1 Install and Configure XAMPP
-------------------------------
1. Download and install XAMPP from: https://www.apachefriends.org/download.html
2. Launch XAMPP Control Panel
3. Start Apache and MySQL services

2.2 Set Up Project Files
------------------------
Option A: Using Git
1. Open terminal/command prompt
2. Run: git clone [your-repository-url]
3. Move the cloned folder to: C:\xampp\htdocs\[project-folder]

Option B: Manual Download
1. Download the project ZIP file
2. Extract to: C:\xampp\htdocs\[project-folder]

2.3 Import Database
-------------------
1. Open phpMyAdmin in your browser: http://localhost/phpmyadmin
2. Create new database: price_comparison_db
3. Click "Import" tab
4. Select the SQL file from project's database/ folder
5. Click "Go" to import

2.4 Configure Application
-------------------------
Edit the following files if needed:

A. Database Configuration (api.php or config.php):
-------------------------------------------------
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'price_comparison_db';

3. TESTING CREDENTIALS
----------------------
Customer Account:
Name: Sibusiso Khumalo
Username: sibusiso_c
Password: test123!223U

Seller Accounts:
1. Michelin Tyre Company SA
Username: michelin_tyres_za
Password: MichelinSecure@2025

2. Kumho Tyres SA
Username: kumho_tyres_za
Password: KumhoSecure@2025

3. Bridgestone SA
Username: bridgestone_sa
Password: BridgestoneSA@2025

Admin Account:
Name: Teboho
Username: admin_za
Password: Admin@2023

4. RUNNING THE APPLICATION
--------------------------
1. Open web browser
2. Navigate to: http://localhost/[project-folder]
3. Use the login credentials above to test different user roles

5. TROUBLESHOOTING
------------------
Issue: Page not loading
- Verify Apache is running in XAMPP
- Check project files are in htdocs folder

Issue: Database connection error
- Verify MySQL is running
- Check database credentials in config files

Issue: Missing dependencies
- Ensure all PHP extensions are enabled in php.ini:
  - pdo_mysql
  - mysqli
  - session