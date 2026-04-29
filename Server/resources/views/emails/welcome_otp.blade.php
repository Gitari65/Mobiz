<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Welcome to MOBIz</title>
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
      background: linear-gradient(90deg, #667eea, #764ba2);
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
    }
    .intro {
      color: #333;
      margin: 0 0 15px 0;
      line-height: 1.6;
    }
    .company-info {
      background: #f0f4ff;
      padding: 15px;
      border-radius: 6px;
      margin: 15px 0;
      border-left: 4px solid #667eea;
    }
    .otp-section {
      margin: 25px 0;
      text-align: center;
    }
    .otp-label {
      font-size: 12px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 8px;
    }
    .otp-code {
      font-size: 28px;
      font-weight: 700;
      background: #f1f5f9;
      padding: 16px;
      display: inline-block;
      border-radius: 6px;
      letter-spacing: 4px;
      font-family: 'Courier New', monospace;
      color: #667eea;
    }
    .otp-expiry {
      font-size: 12px;
      color: #999;
      margin-top: 8px;
    }
    .instructions {
      background: #f8f9fa;
      padding: 20px;
      border-radius: 6px;
      margin: 20px 0;
    }
    .instructions h3 {
      margin: 0 0 10px 0;
      color: #333;
      font-size: 14px;
    }
    .instructions ol {
      margin: 0;
      padding-left: 20px;
    }
    .instructions li {
      margin: 6px 0;
      color: #555;
      font-size: 13px;
    }
    .security-notice {
      background: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #856404;
    }
    .security-notice strong {
      display: block;
      margin-bottom: 5px;
    }
    .footer-text {
      margin: 0;
      color: #666;
      font-size: 12px;
      line-height: 1.6;
    }
    .email-footer {
      padding: 20px;
      text-align: center;
      font-size: 12px;
      color: #9aa0a6;
      background: #f7fafc;
      border-top: 1px solid #e0e0e0;
    }
    .divider {
      height: 1px;
      background: #e0e0e0;
      margin: 20px 0;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-card">
      <div class="email-header">
        <h1>🎉 Welcome to MOBIz</h1>
      </div>
      
      <div class="email-body">
        <p class="greeting">Hello <strong>{{ $name }}</strong>,</p>
        
        <p class="intro">
          Thank you for registering with MOBIz! Your business account has been successfully created and is now ready to use.
        </p>
        
        @if ($company)
        <div class="company-info">
          <strong>📊 Company:</strong> {{ $company }}<br/>
          <strong>Platform:</strong> MOBIz POS System
        </div>
        @endif
        
        <p class="intro">
          To get started, please use your temporary password below to log in to your account:
        </p>
        
        <div class="otp-section">
          <div class="otp-label">Your Temporary Password</div>
          <div class="otp-code">{{ $otp }}</div>
          <div class="otp-expiry">⏱️ This password works immediately</div>
        </div>
        
        <div class="instructions">
          <h3>🚀 How to Get Started:</h3>
          <ol>
            <li>Go to the MOBIz login page</li>
            <li>Enter your email address</li>
            <li>Use the temporary password shown above</li>
            <li>You'll be prompted to create a new permanent password</li>
            <li>Start managing your business!</li>
          </ol>
        </div>
        
        <div class="security-notice">
          <strong>🔒 Security Information</strong>
          For your account security, you will be required to create a new password after your first login. This temporary password cannot be reused.
        </div>
        
        <div class="divider"></div>
        
        <p class="footer-text">
          <strong>Need Help?</strong> If you didn't create this account or have any questions, please contact our support team.
        </p>
        
        <p class="footer-text">
          Best regards,<br/>
          <strong>The MOBIz Team</strong>
        </p>
      </div>
      
      <div class="email-footer">
        <p style="margin: 0;">© {{ date('Y') }} MOBIz — POS Management System</p>
        <p style="margin: 8px 0 0 0; font-size: 11px;">Secure Business Management Solution</p>
      </div>
    </div>
  </div>
</body>
</html>
