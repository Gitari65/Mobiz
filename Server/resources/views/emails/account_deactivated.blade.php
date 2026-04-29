<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Account Deactivated - MOBIz POS</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #111;
      margin: 0;
      padding: 0;
      background: #f7fafc;
    }
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      padding: 24px;
    }
    .email-card {
      background: #fff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .email-header {
      background: linear-gradient(90deg, #ef4444, #dc2626);
      color: #fff;
      padding: 20px;
      text-align: center;
    }
    .email-header h1 {
      margin: 0;
      font-size: 24px;
    }
    .email-body {
      padding: 30px;
    }
    .greeting {
      font-size: 16px;
      margin: 0 0 20px 0;
      font-weight: 600;
    }
    .intro {
      color: #333;
      margin: 0 0 20px 0;
      line-height: 1.6;
    }
    .deactivated-badge {
      display: inline-block;
      background: #fee2e2;
      color: #7f1d1d;
      padding: 8px 14px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600;
      margin: 15px 0;
    }
    .info-box {
      background: #fef2f2;
      padding: 15px;
      border-radius: 6px;
      margin: 15px 0;
      border-left: 4px solid #ef4444;
    }
    .info-box strong {
      display: block;
      color: #991b1b;
      margin-bottom: 8px;
    }
    .info-box p {
      margin: 0;
      color: #7f1d1d;
      font-size: 14px;
      line-height: 1.5;
    }
    .reason-section {
      background: #f9fafb;
      padding: 15px;
      border-radius: 6px;
      margin: 20px 0;
      border: 1px solid #e5e7eb;
    }
    .reason-section h3 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 14px;
    }
    .reason-section p {
      margin: 0;
      color: #666;
      font-size: 13px;
      line-height: 1.6;
    }
    .contact-info {
      background: #f0f9ff;
      padding: 15px;
      border-radius: 6px;
      margin: 20px 0;
      border-left: 4px solid #0284c7;
      color: #0c4a6e;
      font-size: 13px;
      line-height: 1.6;
    }
    .contact-info strong {
      display: block;
      margin-bottom: 8px;
      color: #075985;
      font-size: 14px;
    }
    .important-note {
      background: #fef3c7;
      border-left: 4px solid #fbbf24;
      padding: 15px;
      border-radius: 4px;
      margin: 20px 0;
      color: #78350f;
      font-size: 13px;
      line-height: 1.6;
    }
    .important-note strong {
      display: block;
      margin-bottom: 8px;
    }
    .footer {
      padding: 20px;
      border-top: 1px solid #e2e8f0;
      text-align: center;
      color: #999;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-card">
      <div class="email-header">
        <h1>🔒 Account Deactivated</h1>
      </div>
      <div class="email-body">
        <p class="greeting">Hello {{ $name }},</p>

        <p class="intro">
          We are writing to inform you that your account for {{ $company }} has been deactivated by our administrative team.
        </p>

        <div class="deactivated-badge">⛔ Account Inactive</div>

        <div class="info-box">
          <strong>What This Means</strong>
          <p>
            Your account is no longer active. You will not be able to log in or access any MOBIz POS features. Your company's data remains secure and archived.
          </p>
        </div>

        @if($reason)
        <div class="reason-section">
          <h3>📝 Reason for Deactivation</h3>
          <p>{{ $reason }}</p>
        </div>
        @endif

        <div class="contact-info">
          <strong>Questions or Concerns?</strong>
          If you believe this was done in error or need more information, please contact our support team immediately. We're here to help resolve any issues.
        </div>

        <div class="important-note">
          <strong>📌 Important</strong>
          Do not attempt to use this account. If you need to regain access, please contact our support team at support@mobiz.com or call our help desk with your account details.
        </div>

        <p style="color: #666; margin: 20px 0 0 0; line-height: 1.6; font-size: 13px;">
          Thank you for using MOBIz POS. We appreciate your business and hope to serve you again in the future.
        </p>
      </div>
      <div class="footer">
        <p>© 2026 MOBIz POS. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
