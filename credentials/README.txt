Google Sheets API – Service Account setup
===========================================

1. Go to https://console.cloud.google.com/ and create or select a project.
2. Enable "Google Sheets API" (APIs & Services → Library → search "Google Sheets API").
3. Create a Service Account: APIs & Services → Credentials → Create Credentials → Service Account.
   Download the JSON key and save it here as: google-service-account.json
   (This file is ignored by git. Do not commit it.)
4. Share your Google Sheet with the service account email
   (e.g. something@your-project.iam.gserviceaccount.com).
   Use Editor access if you want driver sync (Add Driver → append to sheet); Viewer is enough for seed-only.
5. Optional: In wp-config.php you can set:
   define('COURIER_GOOGLE_CREDENTIALS_PATH', '/full/path/to/google-service-account.json');
   define('COURIER_GOOGLE_SPREADSHEET_ID', '1w-9PfeN198UoLp-LO-ZFUYYjWiewuIfsp9r-2lY_Xec');
   define('COURIER_GOOGLE_DRIVERS_SHEET', 'kit_drivers');       // optional; default: kit_drivers
   define('COURIER_GOOGLE_WAYBILLS_SHEET', 'kit_waybills');     // optional; default: kit_waybills
   define('COURIER_GOOGLE_WAYBILL_ITEMS_SHEET', 'kit_waybill_items');  // optional; default: kit_waybill_items
   define('COURIER_GOOGLE_CUSTOMERS_SHEET', 'kit_customers');   // optional; default: kit_customers
   define('COURIER_GOOGLE_DELIVERIES_SHEET', 'kit_deliveries'); // optional; default: kit_deliveries
   If not set, the plugin uses credentials/google-service-account.json and the 08600 waybills spreadsheet by default.

Production (e.g. www.08600africa.com) – Sheet not connecting
-----------------------------------------------------------
The credentials JSON is not included in the plugin zip (it is in .gitignore). On the live server:

A) Upload the JSON file:
   - Upload google-service-account.json to the server (e.g. into this plugin's credentials/ folder via FTP/SFTP),
   - Then in wp-config.php add:
     define('COURIER_GOOGLE_CREDENTIALS_PATH', '/absolute/path/to/wp-content/plugins/courier-finance-plugin/credentials/google-service-account.json');
   Replace with the real absolute path on your server (ask your host or use a file manager to see it).

B) Or put the file outside the web root (more secure) and point to it:
   define('COURIER_GOOGLE_CREDENTIALS_PATH', '/home/youruser/private/google-service-account.json');

C) Ensure the file is readable by the web server (e.g. chmod 640 and correct owner).
D) Share the Google Sheet with the service account email from the JSON (client_email field).
E) Reload the Google Sheets Test page in WP Admin to verify connection.

DB-to-Sheet sync: Add/Update/Delete for Drivers, Waybills, Waybill Items, Customers, and Deliveries are synced
   to kit_drivers, kit_waybills, kit_waybill_items, kit_customers, kit_deliveries tabs. Use constants to override.

Usage in code:
  $rows = Courier_Google_Sheets::get_values('', 'Sheet1!A1:Z100');
  if (Courier_Google_Sheets::is_configured()) { ... }
