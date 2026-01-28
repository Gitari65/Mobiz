<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Your MOBIz Login Code</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding: 24px; background:#f7fafc;">
        <table width="600" align="center" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:8px; overflow:hidden;">
          <tr>
            <td style="padding:20px; text-align:center; background:linear-gradient(90deg,#667eea,#764ba2); color:#fff;">
              <h1 style="margin:0;">Login verification</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:20px;">
              <p>Hi {{ $name }},</p>
              <p>We noticed a successful sign-in to your MOBIz account{{ $company ? ' for '.$company : '' }}.</p>
              <p>Your one-time verification code is:</p>
              <p style="font-size:18px; font-weight:700; background:#f1f5f9; padding:12px; display:inline-block; border-radius:6px;">{{ $otp }}</p>
              <p>This code expires in 10 minutes. If you did not initiate this login, please reset your password immediately.</p>
              <p>Thanks,<br/>The MOBIz Team</p>
            </td>
          </tr>
          <tr>
            <td style="padding:12px; text-align:center; font-size:12px; color:#9aa0a6; background:#f7fafc;">
              © {{ date('Y') }} MOBIz — POS System
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
