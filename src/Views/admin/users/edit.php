<h2><i class="bi bi-person-gear"></i> Редактиране на потребител</h2>

<?php
// Determine user type from roles
$userType = 'student'; // Default
if (in_array('ADMIN', $userRoles ?? [])) {
    $userType = 'admin';
} elseif (in_array('LECTURER', $userRoles ?? [])) {
    $userType = 'lecturer';
} elseif (in_array('ASSISTANT', $userRoles ?? [])) {
    $userType = 'assistant';
}
?>

<a href="/admin/users" class="btn btn-secondary mb-3">
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
        <form method="POST" action="/admin/users/<?= $user['id'] ?>/edit">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Имейл <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Нова парола</label>
                    <input type="password" class="form-control" id="password" name="password" minlength="8">
                    <small class="text-muted">Оставете празно, ако не искате да променяте паролата.</small>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Име <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                           value="<?= htmlspecialchars($user['first_name']) ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Фамилия <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                           value="<?= htmlspecialchars($user['last_name']) ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Тип потребител</label>
                <p class="form-control-plaintext">
                    <?php
                    $types = [
                        'admin' => 'Администратор',
                        'lecturer' => 'Преподавател',
                        'assistant' => 'Асистент',
                        'student' => 'Студент'
                    ];
                    echo $types[$userType] ?? 'Неизвестен';
                    ?>
                </p>
                <small class="text-muted">Типът на потребителя не може да бъде променен.</small>
            </div>
            
            <?php if ($userType === 'lecturer' || $userType === 'assistant'): ?>
                <div class="border p-3 mb-3 rounded">
                    <h6>Данни за преподавател</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="title" class="form-label">Титла</label>
                            <select class="form-select" id="title" name="title">
                                <option value="">-- Без --</option>
                                <option value="гл. ас." <?= ($lecturer['title'] ?? '') === 'гл. ас.' ? 'selected' : '' ?>>гл. ас.</option>
                                <option value="доц." <?= ($lecturer['title'] ?? '') === 'доц.' ? 'selected' : '' ?>>доц.</option>
                                <option value="проф." <?= ($lecturer['title'] ?? '') === 'проф.' ? 'selected' : '' ?>>проф.</option>
                                <option value="ас." <?= ($lecturer['title'] ?? '') === 'ас.' ? 'selected' : '' ?>>ас.</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="department" class="form-label">Катедра</label>
                            <input type="text" class="form-control" id="department" name="department"
                                   value="<?= htmlspecialchars($lecturer['department'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_hours_per_week" class="form-label">Макс. часове/седмица</label>
                            <input type="number" class="form-control" id="max_hours_per_week" name="max_hours_per_week"
                                   value="<?= $lecturer['max_hours_per_week'] ?? 20 ?>">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($userType === 'student'): ?>
                <div class="border p-3 mb-3 rounded">
                    <h6>Данни за студент</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="faculty_number" class="form-label">Факултетен номер</label>
                            <input type="text" class="form-control" id="faculty_number" name="faculty_number"
                                   value="<?= htmlspecialchars($student['faculty_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="group_id" class="form-label">Група</label>
                            <select class="form-select" id="group_id" name="group_id">
                                <option value="">-- Изберете --</option>
                                <?php foreach ($groups ?? [] as $group): ?>
                                    <option value="<?= $group['id'] ?>" <?= ($student['group_id'] ?? '') == $group['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($group['major_name']) ?> - 
                                        Поток <?= $group['stream_number'] ?>, Група <?= $group['group_number'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="enrollment_year" class="form-label">Година на записване</label>
                            <input type="number" class="form-control" id="enrollment_year" name="enrollment_year"
                                   value="<?= $student['enrollment_year'] ?? '' ?>">
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1"
                       <?= $user['is_active'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="is_active">Активен</label>
            </div>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази промените
            </button>
        </form>
    </div>
</div>
