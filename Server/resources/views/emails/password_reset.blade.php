<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Password Reset</title>
</head>
<body>
  <p>Hello {{ $name }},</p>
  <p>This is to inform you that your password has been reset by an administrator.</p>
  <p><strong>Temporary password:</strong> {{ $tempPassword }}</p>
  @if($mustChange)
    <p>For security, you are required to change your password on your next login.</p>
  @endif
  <p>If you did not request this, please contact support immediately.</p>
  <p>Regards,<br/>Mobiz Team</p>
</body>
</html>
