<h2><i class="bi bi-person-plus"></i> Нов потребител</h2>

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
        <form method="POST" action="/admin/users/create">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Имейл <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Парола <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="first_name" class="form-label">Име <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="first_name" name="first_name"
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="last_name" class="form-label">Фамилия <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="last_name" name="last_name"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Тип потребител <span class="text-danger">*</span></label>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="user_type" id="type_admin" value="admin"
                           <?= ($old['user_type'] ?? '') === 'admin' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="type_admin">Администратор</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="user_type" id="type_lecturer" value="lecturer"
                           <?= ($old['user_type'] ?? '') === 'lecturer' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="type_lecturer">Преподавател</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="user_type" id="type_assistant" value="assistant"
                           <?= ($old['user_type'] ?? '') === 'assistant' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="type_assistant">Асистент</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="user_type" id="type_student" value="student"
                           <?= ($old['user_type'] ?? 'student') === 'student' ? 'checked' : '' ?>>
                    <label class="form-check-label" for="type_student">Студент</label>
                </div>
            </div>
            
            <!-- Lecturer specific fields -->
            <div id="lecturer-fields" class="d-none border p-3 mb-3 rounded">
                <h6>Данни за преподавател</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="title" class="form-label">Титла</label>
                        <select class="form-select" id="title" name="title">
                            <option value="">-- Без --</option>
                            <option value="гл. ас.">гл. ас.</option>
                            <option value="доц.">доц.</option>
                            <option value="проф.">проф.</option>
                            <option value="ас.">ас.</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="department" class="form-label">Катедра</label>
                        <input type="text" class="form-control" id="department" name="department">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="max_hours_per_week" class="form-label">Макс. часове/седмица</label>
                        <input type="number" class="form-control" id="max_hours_per_week" name="max_hours_per_week" value="20">
                    </div>
                </div>
            </div>
            
            <!-- Student specific fields -->
            <div id="student-fields" class="border p-3 mb-3 rounded">
                <h6>Данни за студент</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="faculty_number" class="form-label">Факултетен номер <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="faculty_number" name="faculty_number"
                               value="<?= htmlspecialchars($old['faculty_number'] ?? '') ?>">
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="group_id" class="form-label">Група <span class="text-danger">*</span></label>
                        <select class="form-select" id="group_id" name="group_id">
                            <option value="">-- Изберете --</option>
                            <?php foreach ($groups ?? [] as $group): ?>
                                <option value="<?= $group->id ?>" <?= ($old['group_id'] ?? '') == $group->id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($group->major_name) ?> - 
                                    Поток <?= $group->stream_number ?>, Група <?= $group->group_number ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="enrollment_year" class="form-label">Година на записване</label>
                        <input type="number" class="form-control" id="enrollment_year" name="enrollment_year"
                               value="<?= $old['enrollment_year'] ?? date('Y') ?>">
                    </div>
                </div>
            </div>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">Активен</label>
            </div>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Създай
            </button>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('input[name="user_type"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.getElementById('lecturer-fields').classList.toggle('d-none', !['lecturer', 'assistant'].includes(this.value));
        document.getElementById('student-fields').classList.toggle('d-none', this.value !== 'student');
    });
});
</script>
