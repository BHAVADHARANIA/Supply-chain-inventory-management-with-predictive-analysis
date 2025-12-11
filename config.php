<?php
/**
 * config.php
 * Centralized Configuration for SCM System.
 * Ensures critical credentials are in one place for security and maintenance.
 */

// --- Database Configuration (Defined in db.php, but kept here for reference)
// define('DB_SERVER', 'localhost');
// define('DB_USERNAME', 'root');
// define('DB_PASSWORD', '');
// define('DB_NAME', 'scm_predictive_db');

// --- Alert Service Configuration (Replace placeholders with REAL CREDENTIALS)
define('TWILIO_ACCOUNT_SID', 'AC01999fce7b3d1ae08cd6f15c1437da0a'); // Your Account SID
define('TWILIO_AUTH_TOKEN', '7ddb6f594fb4df63b79070e568aaaec7');   // Your Auth Token
define('TWILIO_PHONE_NUMBER', '+919597032013'); // Your Twilio/SMS Service Phone Number

// --- System Configuration
define('LOW_STOCK_THRESHOLD', 10); // Global threshold for critical alerts
define('ADMIN_PHONE_NUMBER', '+15551234567'); // Main recipient for critical SMS alerts

// --- Deployment Configuration
// IMPORTANT: Change this base URL to your actual domain/project path when live!
define('BASE_URL', 'http://localhost/scm_system/');