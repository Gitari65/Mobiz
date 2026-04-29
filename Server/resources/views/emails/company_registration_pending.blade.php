<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>New Registration Pending Review</title>
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
      font-weight: 600;
    }
    .intro {
      color: #333;
      margin: 0 0 20px 0;
      line-height: 1.6;
    }
    .info-section {
      background: #f0f4ff;
      padding: 20px;
      border-radius: 6px;
      margin: 20px 0;
      border-left: 4px solid #667eea;
    }
    .info-row {
      display: flex;
      margin: 12px 0;
      line-height: 1.5;
    }
    .info-label {
      font-weight: 600;
      color: #667eea;
      width: 120px;
      flex-shrink: 0;
    }
    .info-value {
      color: #333;
      flex: 1;
      word-break: break-word;
    }
    .pending-badge {
      display: inline-block;
      background: #fcd34d;
      color: #92400e;
      padding: 6px 12px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 600;
      margin: 15px 0;
    }
    .action-message {
      background: #fef3c7;
      border-left: 4px solid #fbbf24;
      padding: 15px;
      border-radius: 4px;
      margin: 20px 0;
      color: #78350f;
      font-size: 14px;
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
        <h1>📋 New Registration</h1>
      </div>
      <div class="email-body">
        <p class="greeting">Hello Admin,</p>

        <p class="intro">
          A new company has registered on MOBIz POS. The registration is now pending your review and approval.
        </p>

        <div class="pending-badge">⏳ PENDING VERIFICATION</div>

        <div class="info-section">
          <div class="info-row">
            <span class="info-label">Company:</span>
            <span class="info-value">{{ $companyName }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Owner:</span>
            <span class="info-value">{{ $ownerName }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $ownerEmail }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Category:</span>
            <span class="info-value">{{ $category }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Phone:</span>
            <span class="info-value">{{ $phone }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">Address:</span>
            <span class="info-value">{{ $address }}, {{ $city }}, {{ $county }}</span>
          </div>
          <div class="info-row">
            <span class="info-label">KRA PIN:</span>
            <span class="info-value">{{ $kraPin }}</span>
          </div>
        </div>

        <div class="action-message">
          <strong>Next Steps:</strong> Please review this registration in your SuperUser Dashboard and either approve or reject the company. When approved, the owner will receive their credentials via email.
        </div>

        <p style="color: #666; margin: 20px 0 0 0; line-height: 1.6;">
          Thank you for using MOBIz POS!
        </p>
      </div>
      <div class="footer">
        <p>© 2026 MOBIz POS. All rights reserved.</p>
      </div>
    </div>
  </div>
</body>
</html>
