# Settings Module - Complete Documentation

## Overview
The Settings module provides comprehensive configuration management for all user roles (Cashier, Admin, SuperUser) in the POS system.

---

## Settings Features by Role

### 🔹 CASHIER Settings
**Access Level:** Personal settings only  
**Route:** `/settings`

#### Available Options:
1. **Personal Preferences**
   - Theme (Light/Dark mode)
   - Language selection
   - Date & time format
   - Items per page in listings
   - Default landing page

2. **Notification Preferences**
   - Email notifications (on/off)
   - Push notifications (on/off)
   - Low stock alerts
   - Sale alerts
   - Report alerts

3. **Receipt Preferences**
   - Auto-print receipt after sale
   - Preferred printer selection

4. **Dashboard Customization**
   - Widget visibility toggles
   - Dashboard layout preferences

---

### 🔹 ADMIN Settings
**Access Level:** Personal + Company settings  
**Route:** `/settings`

#### Available Tabs:

##### Tab 1: Personal Settings
Same as Cashier settings (above)

##### Tab 2: Company Settings

###### Business Configuration
- Business hours (start/end time)
- Timezone
- Currency & symbol
- Decimal places for pricing

###### Tax Settings
- Enable/disable tax
- Tax name (VAT, GST, etc.)
- Tax rate (percentage)
- Tax inclusive pricing toggle

###### Receipt & Invoice Settings
- Receipt header text
- Receipt footer text
- Receipt logo upload/remove
- Auto-print receipt toggle
- Invoice prefix (e.g., "INV-")
- Starting invoice number

###### Inventory Settings
- Low stock alerts (on/off)
- Low stock threshold
- Allow negative stock
- Track stock expiry dates

###### Sales Settings
- Require customer information
- Allow discounts
- Maximum discount percentage
- Allow credit sales

###### Notification Settings
- Company-wide email notifications
- SMS notifications
- Notification email address
- Notification phone number

###### Security Settings
- Require receipt approval
- Enable audit logging
- Session timeout (minutes)
- Two-factor authentication

###### Backup Settings
- Auto backup (on/off)
- Backup frequency (daily/weekly/monthly)
- Backup retention days

---

### 🔹 SUPERUSER Settings
**Access Level:** Personal + System-wide settings  
**Route:** `/superuser/settings`

#### Available Tabs:

##### Tab 1: Personal Settings
Same as Cashier settings

##### Tab 2: System Settings

###### General Settings
- System name
- System timezone
- Default language
- Maintenance mode toggle
- System announcement message
- Max users per company
- Default trial days
- Default currency

###### Security Settings
- Enforce strong passwords
- Password expiration days
- Max login attempts
- Account lockout duration
- Session timeout (global)
- Require email verification
- Enable rate limiting
- IP whitelist/blacklist

###### Billing Settings
- Default subscription plan
- Grace period days
- Auto-suspend on payment failure
- Payment gateway settings
- Invoice email template
- Receipt email template
- Late payment fee percentage

###### Performance Settings
- Cache duration (minutes)
- Max database connections
- Log retention days
- Enable query logging
- API rate limit (requests/minute)

###### Feature Toggles (Per Company)
- Expenses module
- Multi-warehouse
- Advanced reports
- Email marketing
- SMS notifications
- Multi-currency
- Barcode scanner
- Loyalty program

---

## Database Structure

### Tables Created:

#### 1. `company_settings`
Stores company-specific settings for admins.

#### 2. `user_settings`
Stores personal preferences for all users.

#### 3. `system_settings`
Stores global system settings (SuperUser only).

#### 4. `email_templates`
Customizable email templates.

#### 5. `feature_toggles`
Enable/disable features per company.

---

## API Endpoints

### User Settings (All Roles)
```
GET    /api/settings/user          # Get user settings
PUT    /api/settings/user          # Update user settings
```

### Company Settings (Admin + SuperUser)
```
GET    /api/settings/company       # Get company settings
PUT    /api/settings/company       # Update company settings
POST   /api/settings/company/upload-logo   # Upload receipt logo
DELETE /api/settings/company/remove-logo   # Remove receipt logo
```

### System Settings (SuperUser Only)
```
GET    /api/settings/system        # Get all system settings
GET    /api/settings/system/{key}  # Get single setting
POST   /api/settings/system        # Create new setting
PUT    /api/settings/system/{id}   # Update setting
DELETE /api/settings/system/{id}   # Delete setting
POST   /api/settings/system/bulk-update  # Bulk update
```

### Public Settings (No Auth)
```
GET    /api/settings/public        # Get public system settings
```

---

## Frontend Routes

### Cashier
- `/settings` → Personal settings page

### Admin
- `/settings` → Tabbed page (Personal + Company)

### SuperUser
- `/superuser/settings` → Tabbed page (Personal + System)

---

## Implementation Status

✅ Database migrations created
✅ Models created (CompanySetting, UserSetting, SystemSetting)
✅ Controllers created with full CRUD
✅ Routes registered in web.php
✅ Sidebar menu updated (Settings moved to last position)
⏳ Frontend Vue components (next step)

---

## Next Steps

1. Create Settings page components:
   - `client/src/pages/Users/SettingsPage.vue` (Cashier + Admin)
   - `client/src/pages/superuser/SettingsPage.vue` (SuperUser)

2. Add routes to router/index.js

3. Run migrations: `php artisan migrate`

4. Test each role's settings access

5. Add validation and error handling

---

## Usage Examples

### Admin: Update Company Settings
```javascript
const updateCompanySettings = async (settings) => {
  const response = await api.put('/api/settings/company', settings);
  return response.data;
};
```

### Cashier: Update Personal Theme
```javascript
const updateTheme = async (theme) => {
  const response = await api.put('/api/settings/user', { theme });
  return response.data;
};
```

### SuperUser: Set System Setting
```javascript
const setSystemSetting = async (key, value, type) => {
  const response = await api.post('/api/settings/system', {
    key, value, type, group: 'general'
  });
  return response.data;
};
```
