<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Account Activated - MOBIz POS</title>
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
      background: linear-gradient(90deg, #10b981, #059669);
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
    .activated-badge {
      display: inline-block;
      background: #d1fae5;
      color: #065f46;
      padding: 8px 14px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600;
      margin: 15px 0;
    }
    .info-box {
      background: #f0fdf4;
      padding: 15px;
      border-radius: 6px;
      margin: 15px 0;
      border-left: 4px solid #10b981;
    }
    .info-box strong {
      display: block;
      color: #065f46;
      margin-bottom: 8px;
    }
    .info-box p {
      margin: 0;
      color: #047857;
      font-size: 14px;
      line-height: 1.5;
    }
    .access-section {
      background: #f9fafb;
      padding: 15px;
      border-radius: 6px;
      margin: 20px 0;
      border: 1px solid #e5e7eb;
    }
    .access-section h3 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 14px;
    }
    .access-section p {
      margin: 8px 0;
      color: #666;
      font-size: 13px;
      line-height: 1.6;
    }
    .credentials-row {
      margin: 12px 0;
      padding: 10px;
      background: #fff;
      border-radius: 4px;
      border-left: 3px solid #667eea;
    }
    .credential-label {
      font-size: 11px;
      color: #999;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }
    .credential-value {
      font-size: 14px;
      font-weight: 600;
      color: #667eea;
      font-family: 'Courier New', monospace;
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
      font-size: 14px;
    }
    .next-steps {
      background: #f0f9ff;
      padding: 15px;
      border-radius: 6px;
      margin: 20px 0;
      border-left: 4px solid #0284c7;
      color: #0c4a6e;
      font-size: 13px;
      line-height: 1.6;
    }
    .next-steps strong {
      display: block;
      margin-bottom: 8px;
      color: #075985;
      font-size: 14px;
    }
    .login-button {
      display: inline-block;
      background: linear-gradient(90deg, #10b981, #059669);
      color: #fff;
      padding: 12px 30px;
      border-radius: 6px;
      text-decoration: none;
      font-weight: 600;
      margin: 20px 0;
      text-align: center;
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
        <h1>✅ Account Activated</h1>
      </div>
      <div class="email-body">
        <p class="greeting">Hello {{ $name }},</p>

        <p class="intro">
          Great news! Your account has been activated by our administrative team. You can now log in and start using MOBIz POS.
        </p>

        <div class="activated-badge">✓ Account Active</div>

        <div class="info-box">
          <strong>You're All Set!</strong>
          <p>
            Your account for {{ $company }} is now active and ready to use. All features are available to you.
          </p>
        </div>

        <div class="access-section">
          <h3>📝 Your Information</h3>
          <p><strong>Email:</strong> {{ $email }}</p>
          <p><strong>Company:</strong> {{ $company }}</p>
        </div>

        @if($temporaryPassword)
        <div class="credentials-row" style="margin: 15px 0; padding: 15px; background: #f9fafb; border-radius: 6px; border: 1px solid #e5e7eb;">
          <div style="margin-bottom: 15px;">
            <strong style="display: block; margin-bottom: 10px; color: #333; font-size: 14px;">🔐 Login Credentials</strong>
            <div class="credential-row">
              <div class="credential-label">Email</div>
              <div class="credential-value">{{ $email }}</div>
            </div>
            <div class="credential-row">
              <div class="credential-label">Temporary Password</div>
              <div class="credential-value">{{ $temporaryPassword }}</div>
            </div>
          </div>
        </div>

        <div class="important-note">
          <strong>⚠️ Important Security Notice</strong>
          This temporary password is provided for first-time login only. You will be required to change it to a secure password of your choice immediately after your first login. Never share your password with anyone.
        </div>
        @endif

        <div class="next-steps">
          <strong>🚀 Next Steps</strong>
          <p>Visit the MOBIz POS login page and sign in with your credentials to start managing your business inventory and operations.</p>
        </div>

        <p style="text-align: center;">
          <a href="http://localhost:3000/login" class="login-button">Login to MOBIz POS</a>
        </p>

        <p style="color: #666; margin: 20px 0 0 0; line-height: 1.6; font-size: 13px;">
          If you have any questions or need assistance, please don't hesitate to contact our support team.
        </p>
      </div>
      <div class="footer">
        <p>© 2026 MOBIz POS. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
