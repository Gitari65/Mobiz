# Admin User Creation with Credential Email Sending

## Overview
When an admin creates a new user for their company, the system automatically generates a temporary password and sends credential email to the newly created user. This ensures secure credential delivery without the admin needing to manually share passwords.

## Implementation Details

### Backend Changes (Laravel - POST /users endpoint)

**File:** `Server/routes/web.php` (lines 463-495)

**Features:**
1. **Authorization Check**: Only admins can create users
2. **Company Association**: New users are automatically associated with the admin's company
3. **Password Auto-Generation**: If no password is provided, system generates a secure 12-character random password
4. **Credential Email Sending**: Automatically sends `WelcomeOneTimePassword` email with temporary credentials
5. **User Status**: 
   - `verified = true` (account is verified)
   - `must_change_password = true` (forces user to change password on first login)
6. **Error Handling**: Email sending failures don't break user creation; logged for monitoring

**Request Validation:**
```json
{
  "name": "string (required, max 255)",
  "email": "email (required, unique)",
  "password": "string (nullable, min 6)",
  "role_id": "integer (required, must exist in roles table)"
}
```

**Response:**
```json
{
  "id": 123,
  "name": "User Name",
  "email": "user@example.com",
  "company_id": 456,
  "role_id": 2,
  "verified": true,
  "must_change_password": true,
  "created_at": "2026-01-28T10:00:00Z"
}
```

### Frontend Changes (Vue - ManageUsersPage.vue)

**File:** `client/src/pages/Admin/ManageUsersPage.vue`

**UI Enhancements:**
1. **Auto-Generate Password Checkbox** (Default: Enabled)
   - Label: "Auto-generate temporary password (recommended)"
   - When checked: System generates secure password, no manual input needed
   - When unchecked: Admin must provide password manually
   - Helper text explains email delivery

2. **Password Input Field**
   - Only visible when auto-generate is unchecked
   - Minimum 6 characters
   - Password field type (hidden input)

3. **User Feedback Messages**
   - If auto-generating: "User created successfully. Temporary password has been sent to user@example.com"
   - If manual password: "User created successfully"

**Password Generation Logic:**
```javascript
generateTemporaryPassword()
- Length: 12 characters
- Includes: Uppercase, Lowercase, Numbers, Special Characters (!@#$%^&*)
- Ensures at least one of each character type
- Randomly shuffled for security
```

**Form Data Initialization:**
```javascript
formData {
  name: '',
  email: '',
  password: '',
  role_id: '',
  id: null,
  autoGeneratePassword: true  // New field - defaults to true
}
```

## Usage Flow

### For Admins Creating Users

1. **Open User Management** → Click "Add New User"
2. **Fill User Details:**
   - Name: Enter user's full name
   - Email: Enter user's email address
   - Role: Select appropriate role from dropdown
   - Password: Leave as-is (auto-generate checked by default)
3. **Click Create**
4. **Confirmation**: Message shows "Temporary password has been sent to user@example.com"
5. **User Receives Email** with login credentials

### For Admins Using Manual Password (Advanced)

1. Uncheck "Auto-generate temporary password" checkbox
2. Enter custom password (min 6 characters)
3. Click Create
4. User still receives credential email with provided password

## Email Template

**Email:** `Server/resources/views/emails/welcome_otp.blade.php`

Contains:
- Welcome message with user's name
- Company name (if available)
- Temporary password in highlighted section
- Instructions to log in and change password
- Security notice about password protection
- Company contact information
- Footer with terms/privacy

**Subject:** "Welcome to MOBIz — Your Temporary Password"

**Mail Driver:** Configured in `.env` (development uses 'log' driver)

## Logging & Monitoring

**Success Log:**
```
Welcome email sent to new user
[user_id: 123, email: user@example.com]
```

**Error Log:**
```
Failed to send welcome email to new user
[error: SMTP error message, user_id: 123]
```

**Log File:** `Server/storage/logs/laravel.log`

## Security Features

1. ✅ **Password Auto-Generation**: Secure random passwords with mixed character types
2. ✅ **Email Transmission**: Credentials sent via secure email (configured SMTP in production)
3. ✅ **Forced Password Change**: Users must change password on first login
4. ✅ **Account Verification**: User accounts are pre-verified
5. ✅ **Company Isolation**: Users can only be created for admin's own company
6. ✅ **Authorization Checks**: Only admins can create users
7. ✅ **Error Handling**: Email failures logged but don't prevent user creation

## Configuration

**Required Environment Variables** (in `.env`):
```
MAIL_DRIVER=log|smtp|mailgun|ses  # Currently using 'log' for development
MAIL_FROM_ADDRESS=noreply@mobiz.com
MAIL_FROM_NAME=MOBIz
```

**Production SMTP Configuration:**
```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
```

## Testing

### Manual Testing Steps

1. **Login as Admin** to your company account
2. **Navigate to Users Management** page
3. **Create New User:**
   - Name: "Test User"
   - Email: "test@example.com"
   - Role: Select any role
   - Auto-generate: Checked (default)
4. **Verify Response:**
   - User created successfully
   - Success message mentions email sent
5. **Check Email:** User receives credential email
6. **Verify Log:** Check `storage/logs/laravel.log` for "Welcome email sent"

### Test Cases

| Test Case | Input | Expected Result |
|-----------|-------|-----------------|
| Auto-generate enabled | Leave password empty | 12-char secure password generated, email sent |
| Auto-generate disabled | Manual password "Test123" | User password set to "Test123", email sent |
| Missing email | Skip email field | Validation error "Email is required" |
| Duplicate email | Email already exists | Validation error "Email must be unique" |
| Admin creates for different company | Different company_id | User still assigned to admin's company |

## Troubleshooting

### Email Not Sending
1. Check log file for error messages: `storage/logs/laravel.log`
2. Verify mail driver configuration in `.env`
3. In development, emails go to log file (not actual email)
4. For production, verify SMTP credentials

### User Not Appearing in List
1. Verify user was created (check response status)
2. Refresh user list page
3. Check database: `SELECT * FROM users WHERE email = 'user@example.com'`
4. Verify user's company_id matches admin's company_id

### Password Not Working
1. Temporary password has been sent - check user's email inbox
2. User account has `must_change_password = true` - password change forced on first login
3. Check if account is verified (`verified = 1` in database)

## Related Resources

- **Mailable Class:** `Server/app/Mail/WelcomeOneTimePassword.php`
- **Email Template:** `Server/resources/views/emails/welcome_otp.blade.php`
- **API Endpoint:** POST `/users` (admin-only)
- **Frontend Component:** `client/src/pages/Admin/ManageUsersPage.vue`
- **Database:** `users` table with company_id, verified, must_change_password fields

## Notes

- ✅ Auto-password generation is **recommended** (checkbox enabled by default)
- ✅ Temporary password is **always** sent via email when user is created
- ✅ Users **must change password** on first login for security
- ✅ System **prevents duplicate emails** across all companies
- ✅ Email sending failures are **logged but don't prevent** user creation
