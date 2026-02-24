<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Password Reset Code</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f6f6f6; color: #333; }
        .container { max-width: 500px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);}
        .code { font-size: 2em; letter-spacing: 4px; background: #f0f0f0; padding: 12px 24px; border-radius: 6px; display: inline-block; margin: 20px 0;}
        .footer { font-size: 0.9em; color: #888; margin-top: 30px;}
    </style>
</head>
<body>
    <div class="container">
        <h2>Password Reset Request</h2>
        <p>Hello, {{ $user->name }}</p>
        <p>We received a request to reset your password. Please use the code below to reset your password:</p>
        <div class="code">
            {{ $code }}
        </div>
        <p>If you did not request a password reset, please ignore this email.</p>
        <div class="footer">
            &mdash; The {{ config('app.name') }} Team
        </div>
    </div>
</body>
</html>