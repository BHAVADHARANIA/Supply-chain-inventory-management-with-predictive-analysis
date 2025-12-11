# Supply-chain-inventory-management-with-predictive-analysis
This project is a streamlined, single-page PHP dashboard designed to provide an Executive Summary of key Supply Chain Management (SCM) metrics. It focuses on Inventory Value, Product Counts, and critical stock-out risks based on static reorder points and system-generated alerts.

✨ Features
Key Performance Indicators (KPIs): Displays the Total Inventory Value, Active Product Count, Items Below Reorder Point (static risk), and Total Critical Alerts (system/predictive risk).

Database Cleanup: Automatically cleans up stale alert entries (alerts for products that have been deleted) to ensure accurate KPI reporting.

Data Visualization: Uses the Chart.js library to present a comparison of the different Critical Alert counts in a visual bar graph.

Modular PHP: Separates the data logic from the presentation layer (although contained in one file, it utilizes external db.php for connection).

Font Awesome Integration: Uses icons for a clean, professional UI.

🛠️ Technology Stack
Backend: PHP (primarily for data fetching and aggregation).

Database: MySQL/MariaDB (via mysqli).

Frontend: HTML5, CSS (minimal styling), Chart.js (for graphing).

Other Dependencies: Font Awesome Icons.

🚀 Installation & Setup
Prerequisites
Web Server: Apache or Nginx.

PHP: Version 7.x or 8.x.

Database: MySQL or MariaDB.

XAMPP/WAMP/MAMP: Recommended for local development.
