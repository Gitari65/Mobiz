# SMS/Email Messaging System Documentation

## Overview

MOBIz POS now includes a comprehensive messaging system that allows admins to send SMS and email communications to customers, suppliers, and staff. The system supports:

- **Pre-built templates** for common POS scenarios
- **Variable substitution** for personalized messages
- **Single and bulk messaging** capabilities
- **SMS via Twilio** (with sandbox mode for testing)
- **Email via Mailtrap** (pre-configured)
- **Message tracking** and delivery status monitoring
- **Campaign management** with scheduling capabilities

## Features

### 📧 Email Capabilities
- ✅ Pre-configured with Mailtrap (sandbox)
- ✅ HTML and plain text support
- ✅ Template-based emails
- ✅ Variable substitution ({{customer_name}}, {{total_amount}}, etc.)
- ✅ Bulk email campaigns
- ✅ Delivery tracking

### 📱 SMS Capabilities
- ✅ Twilio integration with sandbox mode
- ✅ SMS templates with variables
- ✅ Character limit warnings (160 chars)
- ✅ Bulk SMS campaigns
- ✅ Delivery status tracking
- ✅ Free sandbox mode for testing

### 🎯 Use Cases

1. **Welcome & Onboarding**
   - Customer welcome messages
   - New staff notifications
   - Supplier registration confirmations

2. **Transactional**
   - Purchase receipts
   - Invoice confirmations
   - Return processing notifications

3. **Marketing & Promotions**
   - Discount offers
   - Loyalty promotions
   - New product announcements

4. **Operational**
   - Low stock alerts
   - Daily sales summaries
   - Stock transfer confirmations
   - Cash closing reports

5. **Reminders**
   - Invoice payment reminders
   - Overdue payment follow-ups
   - Appointment reminders

## Setup Instructions

### Part 1: Email Configuration (Mailtrap)

Email is already configured! Mailtrap credentials are in your `.env`:

```
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@mobiz.local
MAIL_FROM_NAME=MOBIz POS
```

**Mailtrap Features:**
- Free sandbox for testing
- Unlimited emails in inbox
- Full email preview
- Message tracking
- No actual emails sent during testing

### Part 2: SMS Configuration (Twilio)

#### Step 1: Sign Up for Twilio

1. Go to [https://www.twilio.com/](https://www.twilio.com/)
2. Click "Sign up" and create a free account
3. Verify your email and phone number
4. You'll get $15 trial credit

#### Step 2: Get Twilio Credentials

1. Log in to [Twilio Console](https://www.twilio.com/console)
2. Note your **Account SID** and **Auth Token** (visible on dashboard)
3. Go to "Phone Numbers" → "Manage" → "Active Numbers"
4. You'll see your Twilio phone number (e.g., +1234567890)

#### Step 3: Enable Sandbox Mode

Sandbox mode allows free SMS testing:

1. In Twilio Console, go to **Messaging** → **Services**
2. Create a new Messaging Service (if needed)
3. Enable "Sandbox Mode" for testing
4. Verify recipient phone numbers in sandbox

#### Step 4: Update .env File

Add these lines to your `.env` file:

```
TWILIO_ACCOUNT_SID=your_account_sid_here
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_SANDBOX_MODE=true
```

**Example:**
```
TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
TWILIO_AUTH_TOKEN=your_auth_token_here
TWILIO_PHONE_NUMBER=+1234567890
TWILIO_SANDBOX_MODE=true
```

#### Step 5: Verify Test Phone Numbers

For sandbox testing:

1. Go to Twilio Console → **Messaging** → **Sandbox**
2. Add your phone number to "Sandbox Recipients"
3. Confirm the verification code
4. Now you can send free SMS to verified numbers

### Part 3: Database Setup

Run migrations to create the messaging tables:

```bash
cd Server
php artisan migrate
```

This creates:
- `message_templates` - SMS/Email templates
- `message_logs` - Message send history
- `scheduled_messages` - Recurring campaigns

### Part 4: Seed Default Templates

Initialize default templates for your company:

```bash
php artisan db:seed --class=MessageTemplateSeeder
```

Or via API endpoint:
```
POST /api/messaging/initialize-defaults
```

This creates 9 pre-built templates:
1. Welcome New Customer
2. Purchase Confirmation
3. Low Stock Alert
4. Weekly Sales Report
5. Invoice Payment Reminder
6. Stock Transfer Complete
7. Daily Closing Summary
8. Customer Loyalty Offer
9. Return Processed

## Usage Guide

### Accessing the Messaging Center

Navigate to: **Admin Dashboard** → **Messaging Center**

Or direct URL: `/admin/messaging`

### Sending Messages

#### Single Message

1. Click **"Send Message"** tab
2. Select **"Send to Single Recipient"**
3. Choose channel: Email / SMS / Both
4. Select content type: Use Template or Custom
5. Choose template and enter variables
6. Enter recipient details
7. Click **"Send Message"**

#### Bulk Messages

1. Click **"Send Message"** tab
2. Select **"Send to Multiple Recipients"**
3. Choose channel and template
4. Paste recipients (one per line)
5. Enter campaign name
6. Click **"Send Message"**

#### Custom Content

1. Select **"Custom Message"** option
2. Write subject and body
3. Enter recipient details
4. Send

### Managing Templates

#### Create Template

1. Click **"Templates"** tab
2. Click **"New Template"** button
3. Fill in details:
   - Name: Descriptive name
   - Category: Choose category
   - Type: Email / SMS / Both
   - Subject (for email)
   - Body with {{variables}}
4. Click **"Save Template"**

#### Variable Syntax

Use double curly braces for variables:

```
Hi {{customer_name}},

Thank you for your purchase of {{product_name}} 
for {{total_amount}}.

Your order #{{sale_id}} has been confirmed.

Best regards,
{{company_name}}
```

Available variables (customize in template):
- {{customer_name}}
- {{company_name}}
- {{sale_id}}
- {{total_amount}}
- {{invoice_id}}
- {{due_date}}
- {{product_name}}
- {{discount_percent}}
- {{refund_amount}}
- And many more...

#### Edit Template

1. Go to **"Templates"** tab
2. Find template
3. Click **"Edit"** button
4. Modify content
5. Click **"Save Template"**

#### Delete Template

1. Go to **"Templates"** tab
2. Find template
3. Click **"Delete"**
4. Confirm deletion

### Viewing History

Click **"Message History"** tab to:
- View all sent messages
- Filter by type (SMS/Email)
- Filter by status (Sent/Failed/Pending)
- Search by recipient
- View delivery details

### Analytics

Click **"Statistics"** tab to see:
- Total messages sent
- Failed messages
- Pending messages
- Success rate
- Breakdown by channel

## API Endpoints

### Templates

```
GET    /api/messaging/templates
GET    /api/messaging/templates/{id}
POST   /api/messaging/templates
PUT    /api/messaging/templates/{id}
DELETE /api/messaging/templates/{id}
```

### Sending

```
POST   /api/messaging/send                    # Single message
POST   /api/messaging/send-bulk               # Bulk campaign
POST   /api/messaging/test-template           # Preview template
```

### History & Stats

```
GET    /api/messaging/logs                    # Message history
GET    /api/messaging/stats                   # Statistics
POST   /api/messaging/initialize-defaults     # Seed templates
```

### Example API Calls

#### Send Single Email

```bash
curl -X POST http://localhost:8000/api/messaging/send \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "email",
    "template_id": 1,
    "recipient_email": "customer@example.com",
    "recipient_name": "John Doe",
    "variables": {
      "customer_name": "John",
      "total_amount": "2500 KES"
    }
  }'
```

#### Send Bulk SMS

```bash
curl -X POST http://localhost:8000/api/messaging/send-bulk \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "sms",
    "template_id": 2,
    "recipients": [
      {"phone": "+254700000001", "name": "Alice"},
      {"phone": "+254700000002", "name": "Bob"}
    ],
    "campaign_name": "Weekly Promo"
  }'
```

## Troubleshooting

### SMS Not Sending

**Issue**: SMS fails with "Twilio not configured"

**Solution**:
1. Check `.env` file for Twilio credentials
2. Ensure values don't have extra spaces: `TWILIO_ACCOUNT_SID=ABC123` ✓
3. Verify phone number format: `+254700000000` or `+1234567890`

**Issue**: "Feature not available in your plan"

**Solution**:
- SMS feature is restricted to Professional+ plans
- Check company subscription tier
- Upgrade company if needed

### Emails Not Received

**Issue**: Emails going to Mailtrap, not real addresses

**Solution**: This is expected in development! Mailtrap intercepts all emails.

To send real emails:
1. Update `.env` to use production SMTP
2. Or use Mailgun/SendGrid instead of Mailtrap

### Phone Number Issues

**Issue**: SMS sends but doesn't deliver

**Solution**:
1. Check phone number format (must include country code)
2. Verify number in Twilio Sandbox recipients
3. Check Twilio balance ($15 trial)
4. Review Twilio logs for error details

## Database Schema

### message_templates

```sql
- id (PK)
- company_id (FK)
- name (string)
- slug (string, unique)
- type (enum: sms, email, both)
- category (enum: promotional, transactional, notification, reminder)
- email_subject (string)
- email_body (text)
- sms_body (text)
- variables (json array)
- is_active (boolean)
- recipient_type (enum: customers, suppliers, staff)
```

### message_logs

```sql
- id (PK)
- company_id (FK)
- message_template_id (FK)
- sent_by_user_id (FK)
- type (enum: sms, email)
- recipient_contact (string - email or phone)
- subject (text)
- body (text)
- status (enum: pending, sent, failed, delivered)
- external_id (string - Twilio SID)
- campaign_name (string)
- metadata (json)
- sent_at (timestamp)
- delivered_at (timestamp)
```

### scheduled_messages

```sql
- id (PK)
- company_id (FK)
- message_template_id (FK)
- name (string)
- frequency (enum: once, daily, weekly, monthly)
- schedule_config (json)
- recipient_filters (json)
- is_active (boolean)
- next_send_at (timestamp)
- total_sent (integer)
```

## Best Practices

### SMS Best Practices

1. **Keep it short** - Aim for under 160 characters
2. **Include CTA** - "Reply YES to confirm" or "Visit store today"
3. **Personalize** - Use {{customer_name}} when possible
4. **Opt-in required** - Ensure customers consented to SMS
5. **Business hours** - Send during reasonable times
6. **Test first** - Use Twilio sandbox before production

### Email Best Practices

1. **Clear subject** - Make it descriptive and action-oriented
2. **Branding** - Include company name and logo
3. **Mobile-friendly** - Test on mobile devices
4. **CTA button** - Clear call-to-action
5. **Unsubscribe link** - Required by law in many places
6. **Attachments** - Keep file sizes small

### Template Best Practices

1. **Reusable variables** - Use {{variable}} for dynamic content
2. **Multiple templates** - One for SMS, one for email if needed
3. **Version control** - Keep template history
4. **Testing** - Always preview before sending bulk
5. **Segmentation** - Different templates for different customer groups

## Limitations & Quotas

### Twilio Sandbox Mode

- **Recipients**: Only verified phone numbers
- **Cost**: Free ($0.00/SMS)
- **Prefix**: "[SANDBOX]" added to all messages
- **Perfect for**: Development and testing

### Mailtrap

- **Emails in inbox**: Unlimited in dev
- **Real delivery**: No, intercepted only
- **Perfect for**: Previewing emails, testing templates

### Production Considerations

When moving to production:

1. **Switch to production credentials**
   - Twilio: Remove sandbox mode
   - Mailtrap: Switch to SendGrid/Mailgun or professional email service

2. **Rate limiting**
   - Implement throttling to avoid spam filters
   - Respect rate limits: ~100 SMS/minute

3. **Compliance**
   - GDPR: Get consent before marketing emails
   - CASL: Canadian anti-spam rules
   - SMS: Message frequency regulations

4. **Monitoring**
   - Track delivery rates
   - Monitor bounce/error rates
   - Set up alerts for failures

## Support & Resources

- **Twilio Docs**: https://www.twilio.com/docs/sms
- **Mailtrap Docs**: https://mailtrap.io/blog/
- **Laravel Mail**: https://laravel.com/docs/mail
- **MOBIz Support**: support@mobiz.local

## Next Steps

1. ✅ Configure Twilio credentials
2. ✅ Run database migrations
3. ✅ Seed default templates
4. ✅ Test with single message
5. ✅ Create custom templates
6. ✅ Set up bulk campaigns
7. ✅ Monitor message history
8. ✅ Optimize for production

