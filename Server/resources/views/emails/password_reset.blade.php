<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Password Reset - New Temporary Password</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      line-height: 1.6;
      color: #333;
      max-width: 600px;
      margin: 0 auto;
      padding: 20px;
      background-color: #f4f4f4;
    }
    .email-container {
      background-color: white;
      border-radius: 10px;
      padding: 30px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .email-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 20px;
      border-radius: 8px 8px 0 0;
      text-align: center;
      margin: -30px -30px 30px -30px;
    }
    .email-header h1 {
      margin: 0;
      font-size: 24px;
    }
    .content {
      margin: 20px 0;
    }
    .otp-box {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 25px;
      border-radius: 12px;
      text-align: center;
      margin: 25px 0;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    .otp-code {
      font-size: 36px;
      font-weight: bold;
      letter-spacing: 8px;
      font-family: 'Courier New', monospace;
      margin: 10px 0;
      padding: 15px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 8px;
      display: inline-block;
    }
    .info-box {
      background-color: #e3f2fd;
      border-left: 4px solid #2196f3;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
    }
    .warning {
      background-color: #fff3cd;
      border-left: 4px solid #ffc107;
      padding: 15px;
      margin: 20px 0;
      border-radius: 4px;
    }
    .steps {
      background-color: #f8f9fa;
      padding: 20px;
      border-radius: 8px;
      margin: 20px 0;
    }
    .steps ol {
      margin: 10px 0;
      padding-left: 20px;
    }
    .steps li {
      margin: 8px 0;
    }
    .footer {
      margin-top: 30px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
      font-size: 12px;
      color: #666;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1>🔐 Password Reset</h1>
    </div>
    
    <div class="content">
      <p>Hello <strong>{{ $name }}</strong>,</p>
      
      <p>Your password has been reset successfully. Below is your new temporary password:</p>
      
      <div class="otp-box">
        <div style="font-size: 14px; margin-bottom: 10px;">Your Temporary Password</div>
        <div class="otp-code">{{ $otp }}</div>
        <div style="font-size: 12px; margin-top: 10px; opacity: 0.9;">Use this to log in to your account</div>
      </div>
      
      <div class="steps">
        <strong>📝 Next Steps:</strong>
        <ol>
          <li>Go to the login page</li>
          <li>Enter your email: <strong>{{ $email }}</strong></li>
          <li>Use the temporary password above</li>
          <li>You will be prompted to change your password after login</li>
        </ol>
      </div>
      
      <div class="info-box">
        <p style="margin: 0;"><strong>🔒 Security Notice</strong></p>
        <p style="margin: 5px 0 0 0;">For your security, you will be required to change this temporary password immediately after logging in.</p>
      </div>
      
      <div class="warning">
        <p style="margin: 0;"><strong>⚠️ Didn't request this?</strong></p>
        <p style="margin: 5px 0 0 0;">If you didn't request a password reset, please contact support immediately as your account security may be compromised.</p>
      </div>
    </div>
    
    <div class="footer">
      <p>This is an automated message from {{ $company }} POS System</p>
      <p>&copy; {{ date('Y') }} {{ $company }}. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
