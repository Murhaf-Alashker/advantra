<!-- resources/views/emails/contact_us.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Contact Us Message</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: #0056b3;
        }

        .user-info {
            margin: 15px 0;
            padding: 10px;
            background-color: #eef5ff;
            border-left: 4px solid #0056b3;
            border-radius: 4px;
        }

        .message-body {
            background-color: #f1f1f1;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            white-space: pre-wrap; /* يحافظ على فراغات الأسطر */
        }

        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>New Contact Us Message</h2>

    <p>Hello Team,</p>
    <p>You have received a new message from a user via the Contact Us form. Details are as follows:</p>

    <div class="user-info">
        <p><strong>Name:</strong> {{ $userName }}</p>
        <p><strong>Email:</strong> {{ $userEmail }}</p>
    </div>

    <div class="message-body">
        {{ $body }}
    </div>

    <div class="footer">
        <p>Sent on: {{ \Carbon\Carbon::now()->format('Y-m-d H:i:s') }}</p>
        <p>Please respond to the user as soon as possible.</p>
    </div>
</div>
</body>
</html>
