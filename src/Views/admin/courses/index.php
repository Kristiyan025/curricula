<h2><i class="bi bi-book"></i> Дисциплини</h2>

<div class="row mb-3">
    <div class="col-md-6">
        <a href="/admin/courses/create" class="btn btn-fmi">
            <i class="bi bi-plus-circle"></i> Нова дисциплина
        </a>
        <a href="/admin" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Обратно
        </a>
    </div>
    <div class="col-md-6">
        <form method="GET" action="/admin/courses" class="d-flex">
            <select class="form-select me-2" name="type" onchange="this.form.submit()">
                <option value="">Всички типове</option>
                <option value="MANDATORY" <?= ($selectedType ?? '') === 'MANDATORY' ? 'selected' : '' ?>>Задължителни</option>
                <option value="ELECTIVE" <?= ($selectedType ?? '') === 'ELECTIVE' ? 'selected' : '' ?>>Избираеми</option>
            </select>
        </form>
    </div>
</div>

<?php if (empty($courses)): ?>
    <div class="alert alert-info">
        Няма създадени дисциплини.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Код</th>
                    <th>Име (БГ)</th>
                    <th>Кредити</th>
                    <th>Тип</th>
                    <th>Курс</th>
                    <th>Специалност</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($course->code) ?></code></td>
                        <td><?= htmlspecialchars($course->name_bg) ?></td>
                        <td><?= $course->credits ?></td>
                        <td>
                            <span class="badge bg-<?= $course->is_elective ? 'success' : 'primary' ?>">
                                <?= $course->is_elective ? 'Избираема' : 'Задължителна' ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!$course->is_elective && $course->year): ?>
                                <span class="badge bg-info"><?= $course->year ?> курс</span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($course->major_id): ?>
                                <?php 
                                    $major = array_filter($majors, fn($m) => $m->id == $course->major_id);
                                    $major = reset($major);
                                ?>
                                <?= $major ? htmlspecialchars($major->abbreviation) : '-' ?>
                            <?php else: ?>
                                <span class="text-muted">Всички</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/courses/<?= $course->id ?>/edit" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/admin/courses/<?= $course->id ?>/instances" class="btn btn-sm btn-info">
                                <i class="bi bi-calendar-event"></i> Инстанции
                            </a>
                            <form method="POST" action="/admin/courses/<?= $course->id ?>/delete" class="d-inline"
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
