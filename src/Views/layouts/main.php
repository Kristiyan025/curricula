<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Curricula') ?> | ФМИ</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --fmi-blue: #003366;
            --fmi-gold: #C4A000;
        }
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar-fmi {
            background-color: var(--fmi-blue);
        }
        .navbar-fmi .navbar-brand,
        .navbar-fmi .nav-link {
            color: white;
        }
        .navbar-fmi .nav-link:hover {
            color: var(--fmi-gold);
        }
        main {
            flex: 1;
        }
        footer {
            background-color: var(--fmi-blue);
            color: white;
            padding: 1rem 0;
            margin-top: auto;
        }
        .schedule-table th {
            background-color: var(--fmi-blue);
            color: white;
        }
        .schedule-slot {
            padding: 0.5rem;
            margin-bottom: 0.25rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .slot-lecture {
            background-color: #e3f2fd;
            border-left: 3px solid #1976d2;
        }
        .slot-exercise {
            background-color: #f3e5f5;
            border-left: 3px solid #7b1fa2;
        }
        .slot-elective {
            background-color: #fff3e0;
            border-left: 3px solid #f57c00;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background-color: #f8f9fa;
        }
        .sidebar .nav-link {
            color: #333;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background-color: var(--fmi-blue);
            color: white;
        }
        .card-header-fmi {
            background-color: var(--fmi-blue);
            color: white;
        }
        .btn-fmi {
            background-color: var(--fmi-blue);
            border-color: var(--fmi-blue);
            color: white;
        }
        .btn-fmi:hover {
            background-color: #002244;
            border-color: #002244;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-fmi">
        <div class="container">
            <a class="navbar-brand" href="/">
                <i class="bi bi-calendar3"></i> Curricula
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            Разписания
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/schedule/stream">По поток</a></li>
                            <li><a class="dropdown-item" href="/schedule/group">По група</a></li>
                            <li><a class="dropdown-item" href="/schedule/lecturer">По преподавател</a></li>
                            <li><a class="dropdown-item" href="/schedule/room">По зала</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/schedule/tests">Контролни</a></li>
                            <li><a class="dropdown-item" href="/schedule/exams">Изпити</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/about">За системата</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($user) && $user): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle"></i>
                                <?= htmlspecialchars($user['full_name'] ?? $user['email'] ?? 'Потребител') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if (in_array('ADMIN', $user['roles'] ?? [])): ?>
                                    <li><a class="dropdown-item" href="/admin">Администрация</a></li>
                                <?php endif; ?>
                                <?php if (in_array('LECTURER', $user['roles'] ?? []) || in_array('ASSISTANT', $user['roles'] ?? [])): ?>
                                    <li><a class="dropdown-item" href="/lecturer">Моето разписание</a></li>
                                    <li><a class="dropdown-item" href="/lecturer/preferences">Предпочитания</a></li>
                                <?php endif; ?>
                                <?php if (in_array('STUDENT', $user['roles'] ?? [])): ?>
                                    <li><a class="dropdown-item" href="/student">Моето разписание</a></li>
                                    <li><a class="dropdown-item" href="/student/electives">Избираеми</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout">Изход</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/login">
                                <i class="bi bi-box-arrow-in-right"></i> Вход
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            <?php if (isset($flash) && $flash): ?>
                <?php foreach ($flash as $type => $message): ?>
                    <div class="alert alert-<?= $type === 'error' ? 'danger' : $type ?> alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?= $content ?>
        </div>
    </main>

    <footer>
        <div class="container text-center">
            <p class="mb-0">
                &copy; <?= date('Y') ?> Curricula - Факултет по Математика и Информатика, СУ "Св. Климент Охридски"
            </p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
