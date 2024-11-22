<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Credentials</title>
</head>
<body>
    <h1>Welcome to the Platform!</h1>
    <p>Hello {{ $full_name }},</p>
    <p>Your account has been created. You can log in using the following credentials:</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please make sure to change your password after logging in.</p>
    <p>Thank you!</p>
</body>
</html>
