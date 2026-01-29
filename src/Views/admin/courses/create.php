<h2><i class="bi bi-book"></i> Нова дисциплина</h2>

<a href="/admin/courses" class="btn btn-secondary mb-3">
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
        <form method="POST" action="/admin/courses/create" id="courseCreateForm">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Код <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code"
                           value="<?= htmlspecialchars($old['code'] ?? '') ?>" required maxlength="20">
                    <small class="text-muted">Напр. CS101, MATH201</small>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="name_bg" class="form-label">Име (БГ) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_bg" name="name_bg"
                           value="<?= htmlspecialchars($old['name_bg'] ?? '') ?>" required maxlength="200">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="credits" class="form-label">Кредити <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="credits" name="credits"
                           value="<?= htmlspecialchars($old['credits'] ?? '6') ?>" required min="1" max="30">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="course_type" class="form-label">Тип <span class="text-danger">*</span></label>
                    <select class="form-select" id="course_type" name="course_type" required onchange="toggleMandatoryFields()">
                        <option value="MANDATORY" <?= ($old['course_type'] ?? '') === 'MANDATORY' ? 'selected' : '' ?>>
                            Задължителна
                        </option>
                        <option value="ELECTIVE" <?= ($old['course_type'] ?? '') === 'ELECTIVE' ? 'selected' : '' ?>>
                            Избираема
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3 mandatory-field" id="major_field">
                    <label for="major_id" class="form-label">Специалност</label>
                    <select class="form-select" id="major_id" name="major_id">
                        <option value="">-- Не е зададена --</option>
                        <?php foreach ($majors ?? [] as $major): ?>
                            <option value="<?= $major->id ?>" <?= ($old['major_id'] ?? '') == $major->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($major->name_bg) ?> (<?= htmlspecialchars($major->abbreviation) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3 mandatory-field" id="year_field">
                    <label for="year" class="form-label">Курс (година)</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">-- Не е зададен --</option>
                        <option value="1" <?= ($old['year'] ?? '') === '1' ? 'selected' : '' ?>>1-ви курс</option>
                        <option value="2" <?= ($old['year'] ?? '') === '2' ? 'selected' : '' ?>>2-ри курс</option>
                        <option value="3" <?= ($old['year'] ?? '') === '3' ? 'selected' : '' ?>>3-ти курс</option>
                        <option value="4" <?= ($old['year'] ?? '') === '4' ? 'selected' : '' ?>>4-ти курс</option>
                        <option value="5" <?= ($old['year'] ?? '') === '5' ? 'selected' : '' ?>>5-ти курс</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="outline_bg" class="form-label">Конспект <span class="text-danger">*</span></label>
                <textarea class="form-control" id="outline_bg" name="outline_bg" rows="6" required><?= htmlspecialchars($old['outline_bg'] ?? '') ?></textarea>
                <small class="text-muted">Описание на темите в курса</small>
            </div>
            
            <hr>
            
            <h5><i class="bi bi-diagram-3"></i> Предпоставки (пререквизити)</h5>
            <p class="text-muted small">Курсове, които студентът трябва да е преминал преди да запише този курс</p>
            
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Налични курсове</label>
                    <select class="form-select" id="availableCourses" size="8">
                        <?php foreach ($allCourses ?? [] as $c): ?>
                            <option value="<?= $c->id ?>" data-code="<?= htmlspecialchars($c->code) ?>" data-name="<?= htmlspecialchars($c->name_bg) ?>">
                                <?= htmlspecialchars($c->code . ' - ' . $c->name_bg) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex flex-column justify-content-center align-items-center">
                    <button type="button" class="btn btn-outline-primary mb-2" id="addPrereq" title="Добави">
                        <i class="bi bi-arrow-right"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger" id="removePrereq" title="Премахни">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                </div>
                
                <div class="col-md-5">
                    <label class="form-label">Избрани пререквизити</label>
                    <select class="form-select" id="currentPrereqs" name="prerequisites[]" multiple size="8">
                    </select>
                </div>
            </div>
            
            <hr class="mt-4">
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Създай
            </button>
        </form>
    </div>
</div>

<script>
function toggleMandatoryFields() {
    const courseType = document.getElementById('course_type').value;
    const mandatoryFields = document.querySelectorAll('.mandatory-field');
    
    mandatoryFields.forEach(field => {
        if (courseType === 'ELECTIVE') {
            field.style.display = 'none';
            const select = field.querySelector('select');
            if (select) select.value = '';
        } else {
            field.style.display = 'block';
        }
    });
}

// Add prerequisite
document.getElementById('addPrereq').addEventListener('click', function() {
    const available = document.getElementById('availableCourses');
    const current = document.getElementById('currentPrereqs');
    
    const selected = available.selectedOptions;
    if (selected.length === 0) return;
    
    Array.from(selected).forEach(option => {
        const newOption = option.cloneNode(true);
        newOption.selected = true;
        current.appendChild(newOption);
        option.remove();
    });
});

// Remove prerequisite
document.getElementById('removePrereq').addEventListener('click', function() {
    const available = document.getElementById('availableCourses');
    const current = document.getElementById('currentPrereqs');
    
    const selected = current.selectedOptions;
    if (selected.length === 0) return;
    
    Array.from(selected).forEach(option => {
        const newOption = option.cloneNode(true);
        newOption.selected = false;
        let inserted = false;
        for (let i = 0; i < available.options.length; i++) {
            if (available.options[i].text > newOption.text) {
                available.insertBefore(newOption, available.options[i]);
                inserted = true;
                break;
            }
        }
        if (!inserted) {
            available.appendChild(newOption);
        }
        option.remove();
    });
});

// Ensure all current prerequisites are selected before form submit
document.getElementById('courseCreateForm').addEventListener('submit', function(e) {
    const current = document.getElementById('currentPrereqs');
    Array.from(current.options).forEach(option => {
        option.selected = true;
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleMandatoryFields);
</script>
