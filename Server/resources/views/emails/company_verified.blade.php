<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Account Approved - MOBIz POS</title>
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
    .success-badge {
      display: inline-block;
      background: #d1fae5;
      color: #065f46;
      padding: 8px 14px;
      border-radius: 4px;
      font-size: 13px;
      font-weight: 600;
      margin: 15px 0;
    }
    .company-info {
      background: #f0fdf4;
      padding: 15px;
      border-radius: 6px;
      margin: 15px 0;
      border-left: 4px solid #10b981;
    }
    .credentials-section {
      background: #f9fafb;
      padding: 20px;
      border-radius: 6px;
      margin: 20px 0;
      border: 1px solid #e5e7eb;
    }
    .credentials-title {
      font-weight: 600;
      color: #333;
      margin: 0 0 15px 0;
      display: flex;
      align-items: center;
    }
    .credentials-title::before {
      content: "🔐 ";
      margin-right: 8px;
    }
    .credential-row {
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
      background: #f3f4f6;
      padding: 15px;
      border-radius: 6px;
      margin: 20px 0;
    }
    .next-steps h3 {
      margin: 0 0 12px 0;
      color: #333;
      font-size: 14px;
    }
    .next-steps ol {
      margin: 0;
      padding-left: 20px;
    }
    .next-steps li {
      margin: 8px 0;
      color: #555;
      font-size: 13px;
    }
    .login-button {
      display: inline-block;
      background: linear-gradient(90deg, #667eea, #764ba2);
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
        <h1>✅ Welcome to MOBIz!</h1>
      </div>
      <div class="email-body">
        <p class="greeting">Hello {{ $ownerName }},</p>

        <p class="intro">
          Congratulations! Your company registration has been reviewed and approved. Your account is now active and ready to use.
        </p>

        <div class="success-badge">✓ Account Activated</div>

        <div class="company-info">
          <strong>{{ $companyName }}</strong> is now live on MOBIz POS!
        </div>

        <div class="credentials-section">
          <div class="credentials-title">Login Credentials</div>
          <div class="credential-row">
            <div class="credential-label">Email</div>
            <div class="credential-value">{{ $email }}</div>
          </div>
          <div class="credential-row">
            <div class="credential-label">Temporary Password</div>
            <div class="credential-value">{{ $defaultPassword }}</div>
          </div>
        </div>

        <div class="important-note">
          <strong>⚠️ Important Security Notice</strong>
          This temporary password is provided for first-time login only. You will be required to change it to a secure password of your choice immediately after your first login. Never share your password with anyone.
        </div>

        <div class="next-steps">
          <h3>🚀 Next Steps</h3>
          <ol>
            <li>Visit the MOBIz POS login page</li>
            <li>Enter your email: <strong>{{ $email }}</strong></li>
            <li>Enter the temporary password provided above</li>
            <li>Create a new secure password when prompted</li>
            <li>Start managing your business inventory</li>
          </ol>
        </div>

        <p style="text-align: center;">
          <a href="http://localhost:3000/login" class="login-button">Login to MOBIz POS</a>
        </p>

        <p style="color: #666; margin: 20px 0 0 0; line-height: 1.6;">
          If you have any questions or encounter any issues, please contact our support team. We're here to help!
        </p>
      </div>
      <div class="footer">
        <p>© 2026 MOBIz POS. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
