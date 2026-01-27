<h1><i class="bi bi-speedometer2"></i> Администрация</h1>
<p class="text-muted">Панел за управление на системата Curricula</p>

<div class="row mt-4">
    <!-- Statistics Cards -->
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $stats['majors'] ?? 0 ?></h4>
                        <small>Специалности</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-mortarboard fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/majors" class="text-white text-decoration-none">
                    Управление <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $stats['users'] ?? 0 ?></h4>
                        <small>Потребители</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-people fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/users" class="text-white text-decoration-none">
                    Управление <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $stats['rooms'] ?? 0 ?></h4>
                        <small>Зали</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-door-open fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/rooms" class="text-white text-decoration-none">
                    Управление <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?= $stats['courses'] ?? 0 ?></h4>
                        <small>Курсове</small>
                    </div>
                    <div class="align-self-center">
                        <i class="bi bi-book fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/courses" class="text-white text-decoration-none">
                    Управление <i class="bi bi-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-calendar-plus"></i> Генериране на разписания
            </div>
            <div class="card-body">
                <p>Генерирайте нови варианти на разписания за текущия семестър.</p>
                <p class="mb-1">
                    <strong>Текущ период:</strong> 
                    <?= $period['year'] ?? date('Y') ?>/<?= ($period['year'] ?? date('Y')) + 1 ?>,
                    <?= ($period['semester'] ?? 'WINTER') === 'WINTER' ? 'зимен' : 'летен' ?> семестър
                </p>
                <a href="/admin/schedule" class="btn btn-fmi mt-2">
                    <i class="bi bi-gear"></i> Управление на разписания
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-sliders"></i> Настройки
            </div>
            <div class="card-body">
                <p>Конфигурирайте академичната година, семестри и други системни настройки.</p>
                <a href="/admin/settings" class="btn btn-fmi">
                    <i class="bi bi-gear"></i> Настройки
                </a>
                <a href="/admin/logs" class="btn btn-outline-secondary">
                    <i class="bi bi-journal-text"></i> Системни логове
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-lightning"></i> Бързи действия
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-2">
                        <a href="/admin/majors/create" class="btn btn-outline-primary w-100">
                            <i class="bi bi-plus-circle"></i> Нова специалност
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/admin/users/create" class="btn btn-outline-success w-100">
                            <i class="bi bi-person-plus"></i> Нов потребител
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/admin/rooms/create" class="btn btn-outline-warning w-100">
                            <i class="bi bi-door-open"></i> Нова зала
                        </a>
                    </div>
                    <div class="col-md-3 mb-2">
                        <a href="/admin/courses/create" class="btn btn-outline-info w-100">
                            <i class="bi bi-book"></i> Нов курс
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
