# SMS/Email Messaging System - Quick Reference

## 🚀 Quick Start (5 minutes)

### 1. Get Twilio Sandbox Account
```
1. Visit https://www.twilio.com/ → Sign Up (free account)
2. Verify email & phone
3. Copy Account SID & Auth Token from dashboard
4. Add your phone to Sandbox Recipients
```

### 2. Update .env File
```env
TWILIO_ACCOUNT_SID=your_account_sid
TWILIO_AUTH_TOKEN=your_auth_token
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_SANDBOX_MODE=true
```

### 3. Run Migrations & Seeding
```bash
cd Server
php artisan migrate
php artisan db:seed --class=MessageTemplateSeeder
```

### 4. Access Messaging Center
Navigate to: **Admin Dashboard** → **Messaging Center**

---

## 📧 Email Sending

### From Template
```
1. Click "Send Message"
2. Select channel: Email
3. Content: Use Template
4. Choose template (e.g., "Purchase Confirmation")
5. Fill variables: {{customer_name}}, {{total_amount}}
6. Enter recipient email
7. Click "Send"
```

### Custom Email
```
1. Select "Custom Message"
2. Enter subject & body
3. Enter recipient email
4. Send
```

### Bulk Email Campaign
```
1. Select "Send to Multiple Recipients"
2. Paste emails (one per line):
   customer1@example.com
   customer2@example.com
3. Choose template or custom content
4. Enter campaign name
5. Send
```

---

## 📱 SMS Sending

### From Template
```
1. Click "Send Message"
2. Select channel: SMS
3. Content: Use Template
4. Choose SMS template
5. Fill variables
6. Enter phone: +254700000000 or +1234567890
7. Send
```

### Bulk SMS Campaign
```
1. Select "Send to Multiple Recipients"
2. Paste phone numbers (one per line):
   +254700000001
   +254700000002
3. Choose template
4. Send
```

---

## 📋 Template Variables

### Common Variables
```
{{customer_name}}        - Customer's name
{{company_name}}         - Your company name
{{sale_id}}             - Order/Sale ID
{{invoice_id}}          - Invoice number
{{total_amount}}        - Amount due/paid
{{product_name}}        - Product name
{{due_date}}           - Payment due date
{{discount_percent}}    - Discount percentage
```

### Example Template
```
Hi {{customer_name}},

Your invoice #{{invoice_id}} of {{total_amount}} is due on {{due_date}}.

Please visit our store or call us to pay.

Thank you,
{{company_name}}
```

---

## 📊 Pre-built Templates (9 total)

1. **Welcome New Customer** - Greet new customers
2. **Purchase Confirmation** - Receipt after sale
3. **Low Stock Alert** - Inventory notification
4. **Weekly Sales Report** - Management report
5. **Invoice Payment Reminder** - Payment due notice
6. **Stock Transfer Complete** - Warehouse notification
7. **Daily Closing Summary** - EOD summary
8. **Customer Loyalty Offer** - Promotional
9. **Return Processed** - Return confirmation

---

## 🎯 Common Use Cases

### Scenario 1: Send Receipt via SMS
```
Template: "Purchase Confirmation" (SMS version)
Recipient: Customer phone
Variables:
  - {{customer_name}} = John
  - {{total_amount}} = 5000 KES
  - {{sale_id}} = #12345
```

### Scenario 2: Daily Promotion to Top Customers
```
Template: "Customer Loyalty Offer"
Recipients: All VIP customers (bulk)
Variables:
  - {{discount_percent}} = 15
  - {{product_category}} = Electronics
  - {{expiry_date}} = 2025-02-10
```

### Scenario 3: Stock Alert to Manager
```
Template: "Low Stock Alert" (Email)
Recipient: store_manager@company.com
Variables:
  - {{product_name}} = Premium Oil
  - {{current_stock}} = 5
  - {{minimum_level}} = 10
```

---

## ✅ Testing Messages

### Step 1: Preview Template
```
1. Select template
2. Fill in variables
3. Click "Preview Template"
4. Review email/SMS output
```

### Step 2: Send Test
```
1. Send to your own phone/email first
2. Check Mailtrap inbox for emails
3. Check Twilio SMS delivery
4. Verify variables replaced correctly
```

### Step 3: Go Live
```
1. Verified everything works
2. Send to actual recipients
3. Monitor message history
4. Check delivery status
```

---

## 🔍 Message History

### View All Messages
```
Click "Message History" tab
- See all SMS & emails sent
- Filter by type/status
- Check delivery status
- View error details
```

### Message Details
```
Click "View" on any message
- Full recipient info
- Complete message body
- Delivery status & timestamp
- Error message if failed
```

---

## 📊 Statistics

### Key Metrics
```
- Total Sent: All messages sent
- Failed: Messages that failed
- Pending: Awaiting delivery
- Success Rate: % delivered successfully
- Breakdown by type: Email vs SMS count
```

---

## 🛠️ Create Custom Template

### Steps
```
1. Click "Templates" tab
2. Click "New Template"
3. Fill in details:
   - Name: "My Custom Template"
   - Category: Choose one
   - Type: Email/SMS/Both
4. Add subject (for email)
5. Add body with {{variables}}
6. Click "Save Template"
```

### Template Example
```
Type: Both (Email + SMS)
Category: Promotional

Email Subject: 
"Special Offer for {{customer_name}} - {{discount_percent}}% Off!"

Email Body:
"Dear {{customer_name}},

We have a special {{discount_percent}}% discount on {{product_category}}!

Valid until {{expiry_date}}.

Visit us: {{company_address}}

Thank you!"

SMS Body:
"Hi {{customer_name}}, {{discount_percent}}% off {{product_category}}! Valid till {{expiry_date}}. Visit us!"
```

---

## ⚙️ Configuration

### .env Settings

```env
# Email (Mailtrap - already configured)
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password

# SMS (Twilio - add these)
TWILIO_ACCOUNT_SID=AC1234567890abcdef
TWILIO_AUTH_TOKEN=auth_token_here
TWILIO_PHONE_NUMBER=+14155552671
TWILIO_SANDBOX_MODE=true  # false for production
```

---

## 🚫 Common Issues

### SMS Won't Send
**❌ "Feature not available"**
- Solution: SMS feature limited to Professional+ plans
- Upgrade company subscription

**❌ "Invalid phone number"**
- Solution: Use format: +254700000000 or +1234567890
- Ensure country code included

**❌ "Twilio not configured"**
- Solution: Check .env file, add Twilio credentials
- Restart application after changes

### Emails Not Working
**❌ "Connection refused"**
- Solution: Check Mailtrap credentials in .env
- Verify MAIL_HOST and MAIL_PORT

**❌ "No recipient specified"**
- Solution: Enter valid email address
- Emails cannot be empty

### Message History Empty
**❌ "No messages displayed"**
- Solution: Send messages first
- History shows past 7 days by default
- Check filters aren't too restrictive

---

## 📞 Support

- **Setup Help**: See SMS_EMAIL_SYSTEM_SETUP.md
- **API Details**: Check MessagingController.php
- **Database**: See migrations in database/migrations/

---

## 📋 Checklist

- [ ] Twilio account created
- [ ] Credentials added to .env
- [ ] Migrations run
- [ ] Default templates seeded
- [ ] Phone number verified in Twilio
- [ ] Test email sent
- [ ] Test SMS sent
- [ ] Templates customized
- [ ] Bulk campaign tested
- [ ] Ready for production

---

**Last Updated**: 2025-01-27
**Version**: 1.0
**Status**: ✅ Production Ready

