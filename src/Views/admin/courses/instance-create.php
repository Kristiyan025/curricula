<h2><i class="bi bi-calendar-plus"></i> Нова инстанция на дисциплина</h2>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin">Админ панел</a></li>
        <li class="breadcrumb-item"><a href="/admin/courses">Дисциплини</a></li>
        <li class="breadcrumb-item"><a href="/admin/courses/<?= $course->id ?>/instances"><?= htmlspecialchars($course->name_bg) ?></a></li>
        <li class="breadcrumb-item active">Нова инстанция</li>
    </ol>
</nav>

<a href="/admin/courses/<?= $course->id ?>/instances" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Обратно
</a>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="POST" action="/admin/courses/<?= $course->id ?>/instances/create">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="academic_year" class="form-label">Учебна година <span class="text-danger">*</span></label>
                    <select class="form-select" id="academic_year" name="academic_year" required>
                        <?php 
                        $currentYear = (int)date('Y');
                        for ($y = $currentYear - 1; $y <= $currentYear + 1; $y++): 
                        ?>
                            <option value="<?= $y ?>" <?= ($old['academic_year'] ?? $currentYear) == $y ? 'selected' : '' ?>>
                                <?= $y ?>/<?= $y + 1 ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="semester" class="form-label">Семестър <span class="text-danger">*</span></label>
                    <select class="form-select" id="semester" name="semester" required>
                        <option value="WINTER" <?= ($old['semester'] ?? '') === 'WINTER' ? 'selected' : '' ?>>Зимен</option>
                        <option value="SUMMER" <?= ($old['semester'] ?? '') === 'SUMMER' ? 'selected' : '' ?>>Летен</option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="stream_id" class="form-label">Поток <span class="text-danger">*</span></label>
                    <select class="form-select" id="stream_id" name="stream_id" required>
                        <option value="">-- Изберете --</option>
                        <?php foreach ($streams ?? [] as $stream): ?>
                            <option value="<?= $stream['id'] ?>" <?= ($old['stream_id'] ?? '') == $stream['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($stream['major_name']) ?> - 
                                Поток <?= $stream['stream_number'] ?> (<?= $stream['year'] ?> курс)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="lecturer_id" class="form-label">Лектор <span class="text-danger">*</span></label>
                    <select class="form-select" id="lecturer_id" name="lecturer_id" required>
                        <option value="">-- Изберете --</option>
                        <?php foreach ($lecturers ?? [] as $lecturer): ?>
                            <option value="<?= $lecturer['id'] ?>" <?= ($old['lecturer_id'] ?? '') == $lecturer['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars(($lecturer['title'] ? $lecturer['title'] . ' ' : '') . $lecturer['first_name'] . ' ' . $lecturer['last_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="max_students" class="form-label">Макс. студенти</label>
                    <input type="number" class="form-control" id="max_students" name="max_students"
                           value="<?= htmlspecialchars($old['max_students'] ?? '100') ?>" min="1" max="500">
                </div>
            </div>
            
            <hr>
            
            <h5>Асистенти (опционално)</h5>
            <div class="mb-3">
                <select class="form-select" id="assistants" name="assistants[]" multiple size="5">
                    <?php foreach ($assistants ?? [] as $assistant): ?>
                        <option value="<?= $assistant['id'] ?>">
                            <?= htmlspecialchars($assistant['first_name'] . ' ' . $assistant['last_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Задръжте Ctrl за множествен избор</small>
            </div>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Създай
            </button>
        </form>
    </div>
</div>
