<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Достъпът е забранен | Curricula</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #003366 0%, #4A90A4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            text-align: center;
            color: white;
        }
        .error-code {
            font-size: 8rem;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        .error-message {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
        .btn-home {
            background-color: #C4A000;
            border-color: #C4A000;
            color: #333;
        }
        .btn-home:hover {
            background-color: #D4B000;
            border-color: #D4B000;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code">403</div>
        <div class="error-message">
            <i class="bi bi-shield-exclamation"></i>
            Достъпът е забранен
        </div>
        <p class="mb-4">Нямате права за достъп до тази страница.</p>
        <a href="/" class="btn btn-home btn-lg">
            <i class="bi bi-house"></i> Към началната страница
        </a>
    </div>
</body>
</html>
