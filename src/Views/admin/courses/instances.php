<h2><i class="bi bi-calendar-event"></i> Инстанции на дисциплината</h2>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Админ панел</a></li>
        <li class="breadcrumb-item"><a href="/admin/courses">Дисциплини</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($course->name_bg) ?></li>
    </ol>
</nav>

<div class="mb-3">
    <a href="/admin/courses/<?= $course->id ?>/instances/create" class="btn btn-fmi">
        <i class="bi bi-plus-circle"></i> Нова инстанция
    </a>
    <a href="/admin/courses" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Обратно
    </a>
</div>

<div class="card mb-4">
    <div class="card-header card-header-fmi">
        <i class="bi bi-info-circle"></i> Информация за дисциплината
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Код:</strong> <?= htmlspecialchars($course->code) ?></p>
                <p><strong>Име:</strong> <?= htmlspecialchars($course->name_bg) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Кредити:</strong> <?= $course->credits ?></p>
                <p><strong>Тип:</strong> <?= $course->is_elective ? 'Избираема' : 'Задължителна' ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (empty($instances)): ?>
    <div class="alert alert-info">
        Няма създадени инстанции за тази дисциплина.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Уч. година</th>
                    <th>Семестър</th>
                    <th>Специалност</th>
                    <th>Поток</th>
                    <th>Лектор</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($instances as $instance): ?>
                    <tr>
                        <td><?= $instance->academic_year ?>/<?= $instance->academic_year + 1 ?></td>
                        <td>
                            <span class="badge bg-<?= $instance->semester === 'WINTER' ? 'info' : 'warning' ?>">
                                <?= $instance->semester === 'WINTER' ? 'Зимен' : 'Летен' ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($instance->major_name ?? '-') ?></td>
                        <td>Поток <?= $instance->stream_number ?? '-' ?></td>
                        <td><?= htmlspecialchars($instance->lecturer_name ?? '-') ?></td>
                        <td>
                            <a href="/admin/courses/<?= $course->id ?>/instances/<?= $instance->id ?>/edit" 
                               class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" 
                                  action="/admin/courses/<?= $course->id ?>/instances/<?= $instance->id ?>/delete" 
                                  class="d-inline"
                                  onsubmit="return confirm('Сигурни ли сте?')">
                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
