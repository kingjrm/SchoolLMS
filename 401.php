<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unauthorized - School LMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .error-container {
            text-align: center;
            padding: 2rem;
        }

        .error-code {
            font-size: 4rem;
            font-weight: 700;
            color: #ef4444;
            margin-bottom: 1rem;
        }

        .error-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.5rem;
        }

        .error-message {
            color: #9ca3af;
            margin-bottom: 2rem;
        }

        .error-link {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background-color: #3b82f6;
            color: #fff;
            text-decoration: none;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .error-link:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">401</div>
        <div class="error-title">Unauthorized</div>
        <p class="error-message">You don't have permission to access this resource.</p>
        <a href="login.php" class="error-link">Back to Login</a>
    </div>
</body>
</html>
