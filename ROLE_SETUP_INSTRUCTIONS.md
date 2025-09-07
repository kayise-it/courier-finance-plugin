# 08600 Waybill Plugin - User Roles Setup Instructions

## Overview

This plugin now supports only 3 user roles with specific permissions:

1. **Administrator** - Super user with full access to everything including prices
2. **Data Capturer** - Can input data but cannot see prices
3. **Manager** - Can approve and invoice but cannot see prices

## Setup Instructions

### Step 1: Access WordPress Admin

1. Log into your WordPress admin panel
2. Navigate to **Users** → **All Users**
3. You'll see a dropdown menu for user roles (as shown in the image)

### Step 2: Remove Unwanted Roles

The system will automatically remove these roles:
- Editor
- Author
- Contributor
- Subscriber
- Customer
- Delivery Driver
- Shop Manager

### Step 3: Create Custom Roles

The plugin will automatically create:
- **Data Capturer** role
- **Manager** role
- Update **Administrator** role with plugin capabilities

### Step 4: Assign Roles to Users

1. Go to **Users** → **All Users**
2. For each user, click **Edit**
3. In the **Role** dropdown, select one of the three roles:
   - **Administrator** - For super users who need full access
   - **Data Capturer** - For users who only input data
   - **Manager** - For users who approve and invoice

### Step 5: Test the System

1. Create test users with different roles
2. Log in as each user type
3. Verify that:
   - **Administrators** can see all prices and have full access
   - **Data Capturers** can input data but see "***" instead of prices
   - **Managers** can approve/invoice but see "***" instead of prices

## Role Permissions Summary

### Administrator
- ✅ Full access to everything
- ✅ Can see all prices
- ✅ Can approve waybills
- ✅ Can create invoices
- ✅ Can manage users
- ✅ Can access all system settings

### Data Capturer
- ✅ Can create waybills
- ✅ Can input customer data
- ✅ Can input dimensions and weights
- ❌ Cannot see prices (shows "***")
- ❌ Cannot approve waybills
- ❌ Cannot create invoices

### Manager
- ✅ Can view all waybills
- ✅ Can approve waybills (change approval status)
- ✅ Can create invoices (change invoice status)
- ✅ Can update waybill data
- ❌ Cannot see prices (shows "***")
- ❌ Cannot manage users

## Hidden Fields for Non-Admin Users

The following fields are hidden for Data Capturers and Managers:

### Cost Details Section (Entire Section Hidden)
- Total Mass calculations with rates
- Total Volume calculations with rates
- Charge Basis information
- Waybill Amount
- Waybill Misc Total
- Grand Total
- All pricing calculations

### Weight Section
- Mass Rate (R)
- Mass Total Cost (R)
- Custom manipulator

### Volume Section
- Total Volume (m³)
- Total Volume Charge (R)
- Volume manipulator

### Waybill Details Section
- Waybill Amount field

### Waybill Tables
- Unit Price columns
- Sub Total columns
- Price totals (shows "***")

### Miscellaneous Items Table
- Price columns
- Subtotal columns
- Total amounts (shows "***")

## Troubleshooting

### If roles don't appear in dropdown:
1. Deactivate and reactivate the plugin
2. Check if the user roles were created properly
3. Clear any caching plugins

### If users can't see expected content:
1. Verify the user has the correct role assigned
2. Check if the role has the proper capabilities
3. Test with a different user account

### If prices are still visible:
1. Clear browser cache
2. Log out and log back in
3. Check if the user role was properly assigned

## Support

If you encounter any issues with the role setup, please:
1. Check the WordPress error logs
2. Verify all plugin files are properly uploaded
3. Test with a fresh WordPress installation if needed
