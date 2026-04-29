<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>MOBIz — Your Login Verification Code</title>
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
      padding: 30px 20px;
      text-align: center;
    }
    .email-header h1 {
      margin: 0;
      font-size: 24px;
      font-weight: bold;
    }
    .email-header p {
      margin: 8px 0 0 0;
      font-size: 13px;
      opacity: 0.95;
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
    .code-section {
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
      padding: 25px;
      border-radius: 8px;
      margin: 25px 0;
      text-align: center;
      border: 1px solid rgba(102, 126, 234, 0.2);
    }
    .code-label {
      font-size: 12px;
      color: #666;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 10px;
      display: block;
    }
    .otp-code {
      font-size: 32px;
      font-weight: 700;
      background: #f1f5f9;
      padding: 16px;
      display: inline-block;
      border-radius: 6px;
      letter-spacing: 4px;
      font-family: 'Courier New', monospace;
      color: #667eea;
      margin: 10px 0;
    }
    .expiry-info {
      font-size: 12px;
      color: #999;
      margin-top: 10px;
      display: block;
    }
    .info-box {
      background: #f0f4ff;
      border-left: 4px solid #667eea;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #555;
    }
    .security-note {
      background: #fef3c7;
      border-left: 4px solid #f59e0b;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
      font-size: 13px;
      color: #78350f;
    }
    .security-note strong {
      display: block;
      margin-bottom: 5px;
    }
    .footer-text {
      color: #666;
      font-size: 12px;
      margin: 25px 0 0 0;
      line-height: 1.6;
    }
    .divider {
      height: 1px;
      background: #e0e0e0;
      margin: 20px 0;
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
        <h1>🔐 Login Verification</h1>
        <p>Your one-time verification code</p>
      </div>
      
      <div class="email-body">
        <p class="greeting">Hello <strong>{{ $name }}</strong>,</p>
        
        <p class="intro-text">
          We detected a sign-in attempt to your MOBIz account{{ $company ? ' for ' . $company : '' }}. To keep your account secure, please verify this login attempt by entering the code below.
        </p>
        
        <div class="code-section">
          <span class="code-label">Your Verification Code</span>
          <div class="otp-code">{{ $otp }}</div>
          <span class="expiry-info">⏱️ Code expires in 10 minutes</span>
        </div>
        
        <div class="info-box">
          <strong>📌 How to proceed:</strong><br/>
          Enter this code on the login verification screen. Do not share this code with anyone.
        </div>
        
        <div class="security-note">
          <strong>⚠️ Didn't try to log in?</strong>
          If you didn't attempt to sign in to your account, someone else may be trying to access it. Please change your password immediately and contact our support team if you notice any suspicious activity.
        </div>
        
        <div class="divider"></div>
        
        <p class="footer-text">
          <strong>Stay Safe:</strong> MOBIz will never ask you to share your verification code, password, or personal information via email or text message. Be cautious of phishing attempts.
        </p>
        
        <p class="footer-text">
          Thanks,<br/>
          <strong>The MOBIz Security Team</strong>
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
