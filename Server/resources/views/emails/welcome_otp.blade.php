<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Welcome to MOBIz</title>
</head>
<body style="font-family: Arial, sans-serif; color: #111;">
  <table width="100%" cellpadding="0" cellspacing="0">
    <tr>
      <td style="padding: 24px; background:#f7fafc;">
        <table width="600" align="center" cellpadding="0" cellspacing="0" style="background:#fff; border-radius:8px; overflow:hidden;">
          <tr>
            <td style="padding:20px; text-align:center; background:linear-gradient(90deg,#667eea,#764ba2); color:#fff;">
              <h1 style="margin:0;">Welcome to MOBIz</h1>
            </td>
          </tr>
          <tr>
            <td style="padding:20px;">
              <p>Hi {{ $name }},</p>
              <p>Thank you for creating an account with MOBIz. Your company {{ $company ?? '' }} has been registered successfully and is pending activation.</p>
              <p>Your temporary (one-time) password is:</p>
              <p style="font-size:18px; font-weight:700; background:#f1f5f9; padding:12px; display:inline-block; border-radius:6px;">{{ $otp }}</p>
              <p>Please use this temporary password to log in. For security, you will be prompted to change your password after logging in.</p>
              <p>If you did not request this account, please ignore this email.</p>
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
