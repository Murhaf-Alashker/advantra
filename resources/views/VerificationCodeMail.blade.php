<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Verification Code</title>
</head>
<body>
<h1>Dear {{ $name }},</h1>
<h4>Thank you for signing up! To complete your registration and secure your account, please use the verification code below:</h4>
<h2>Your Verification Code: {{ $verificationCode }}</h2>
<h4>This code is valid for a limited time, so please enter it as soon as possible. If you did not request this code, please ignore this message.</h4>
<h4>If you need any assistance, feel free to reach out to our support team.</h4>
<h4>Best regards,</h4>
<h2>Adventra</h2>
</body>
</html>
