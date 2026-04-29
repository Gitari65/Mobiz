<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MOBIz — Password Reset Code</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      margin: 0;
      padding: 0;
      background-color: #f4f4f4;
    }
    .email-container {
      max-width: 600px;
      margin: 0 auto;
      padding: 24px;
    }
    .email-card {
      background-color: white;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .email-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px 20px;
      text-align: center;
    }
    .email-header h1 {
      margin: 0;
      font-size: 28px;
      font-weight: bold;
    }
    .email-header p {
      margin: 8px 0 0 0;
      font-size: 14px;
      opacity: 0.9;
    }
    .email-body {
      padding: 30px;
    }
    .greeting {
      font-size: 16px;
      margin: 0 0 20px 0;
      font-weight: 500;
    }
    .intro-text {
      color: #555;
      margin: 0 0 25px 0;
      line-height: 1.7;
    }
    .otp-section {
      margin: 30px 0;
      text-align: center;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      padding: 25px;
      border-radius: 8px;
      border: 1px solid rgba(102, 126, 234, 0.2);
    }
    .otp-label {
      font-size: 12px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 10px;
      display: block;
    }
    .otp-code {
      font-size: 32px;
      font-weight: bold;
      letter-spacing: 6px;
      font-family: 'Courier New', monospace;
      color: #667eea;
      margin: 10px 0;
      padding: 15px;
      background: white;
      border-radius: 6px;
      display: inline-block;
    }
    .code-note {
      font-size: 11px;
      color: #999;
      margin-top: 10px;
      display: block;
    }
    .steps-section {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin: 25px 0;
      border-left: 4px solid #667eea;
    }
    .steps-title {
      font-size: 14px;
      font-weight: bold;
      color: #333;
      margin: 0 0 12px 0;
    }
    .steps-section ol {
      margin: 0;
      padding-left: 20px;
    }
    .steps-section li {
      margin: 8px 0;
      color: #555;
      font-size: 13px;
      line-height: 1.5;
    }
    .info-box {
      background-color: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #1565c0;
    }
    .info-box strong {
      display: block;
      margin-bottom: 5px;
    }
    .warning-box {
      background-color: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #856404;
    }
    .warning-box strong {
      display: block;
      margin-bottom: 5px;
    }
    .footer-note {
      color: #666;
      font-size: 12px;
      margin: 20px 0 0 0;
      line-height: 1.6;
    }
    .divider {
      height: 1px;
      background: #e0e0e0;
      margin: 25px 0;
    }
    .email-footer {
      padding: 20px;
      text-align: center;
      font-size: 12px;
      color: #9aa0a6;
      background: #f7fafc;
      border-top: 1px solid #e0e0e0;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-card">
      <div class="email-header">
        <h1>🔐 Password Reset</h1>
        <p>One-time reset code for your account</p>
      </div>
      
      <div class="email-body">
        <p class="greeting">Hello <strong>{{ $name }}</strong>,</p>
        
        <p class="intro-text">
          We received a request to reset the password for your MOBIz account. Below is your temporary password to access your account and set a new permanent password.
        </p>
        
        <div class="otp-section">
          <span class="otp-label">Your Temporary Password</span>
          <div class="otp-code">{{ $otp }}</div>
          <span class="code-note">This password expires in 15 minutes</span>
        </div>
        
        <div class="steps-section">
          <div class="steps-title">📝 What to do next:</div>
          <ol>
            <li>Go to the MOBIz login page</li>
            <li>Enter your email: <strong>{{ $email }}</strong></li>
            <li>Use the temporary password above</li>
            <li>You will be prompted to create a new permanent password immediately</li>
          </ol>
        </div>
        
        <div class="info-box">
          <strong>💡 About Your New Password</strong>
          Make sure to create a strong password that is easy for you to remember but difficult for others to guess. Your new password must be at least 8 characters long.
        </div>
        
        <div class="warning-box">
          <strong>⚠️ Security Alert</strong>
          If you did not request this password reset, your account security may be at risk. Please contact our support team immediately to secure your account.
        </div>
        
        <div class="divider"></div>
        
        <p class="footer-note">
          <strong>Questions or concerns?</strong> If you're having trouble resetting your password or if you didn't request this reset, please reach out to our support team right away. We're here to help keep your account secure.
        </p>
        
        <p class="footer-note">
          Best regards,<br/>
          <strong>The MOBIz Security Team</strong>
        </p>
      </div>
      
      <div class="email-footer">
        <p style="margin: 0;">© {{ date('Y') }} MOBIz — POS Management System</p>
        <p style="margin: 8px 0 0 0; font-size: 11px;">Your trusted business management solution</p>
      </div>
    </div>
  </div>
</body>
</html>
