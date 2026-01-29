<h2><i class="bi bi-book"></i> Редактиране на дисциплина</h2>

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
        <form method="POST" action="/admin/courses/<?= $course->id ?>/edit" id="courseEditForm">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="code" class="form-label">Код <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code"
                           value="<?= htmlspecialchars($course->code) ?>" required maxlength="20">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="name_bg" class="form-label">Име (БГ) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_bg" name="name_bg"
                           value="<?= htmlspecialchars($course->name_bg) ?>" required maxlength="200">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="credits" class="form-label">Кредити <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="credits" name="credits"
                           value="<?= $course->credits ?>" required min="1" max="30">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="course_type" class="form-label">Тип <span class="text-danger">*</span></label>
                    <select class="form-select" id="course_type" name="course_type" required onchange="toggleMandatoryFields()">
                        <option value="MANDATORY" <?= !$course->is_elective ? 'selected' : '' ?>>
                            Задължителна
                        </option>
                        <option value="ELECTIVE" <?= $course->is_elective ? 'selected' : '' ?>>
                            Избираема
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3 mandatory-field" id="major_field">
                    <label for="major_id" class="form-label">Специалност</label>
                    <select class="form-select" id="major_id" name="major_id">
                        <option value="">-- Не е зададена --</option>
                        <?php foreach ($majors as $major): ?>
                            <option value="<?= $major->id ?>" <?= ($course->major_id ?? '') == $major->id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($major->name_bg) ?> (<?= htmlspecialchars($major->abbreviation) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3 mandatory-field" id="year_field">
                    <label for="year" class="form-label">Курс (година)</label>
                    <select class="form-select" id="year" name="year">
                        <option value="">-- Не е зададен --</option>
                        <option value="1" <?= ($course->year ?? '') == 1 ? 'selected' : '' ?>>1-ви курс</option>
                        <option value="2" <?= ($course->year ?? '') == 2 ? 'selected' : '' ?>>2-ри курс</option>
                        <option value="3" <?= ($course->year ?? '') == 3 ? 'selected' : '' ?>>3-ти курс</option>
                        <option value="4" <?= ($course->year ?? '') == 4 ? 'selected' : '' ?>>4-ти курс</option>
                        <option value="5" <?= ($course->year ?? '') == 5 ? 'selected' : '' ?>>5-ти курс</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="outline_bg" class="form-label">Конспект <span class="text-danger">*</span></label>
                <textarea class="form-control" id="outline_bg" name="outline_bg" rows="6" required><?= htmlspecialchars($course->outline_bg ?? '') ?></textarea>
                <small class="text-muted">Описание на темите в курса</small>
            </div>
            
            <hr>
            
            <h5><i class="bi bi-diagram-3"></i> Предпоставки (пререквизити)</h5>
            <p class="text-muted small">Курсове, които студентът трябва да е преминал преди да запише този курс</p>
            
            <div class="row">
                <div class="col-md-5">
                    <label class="form-label">Налични курсове</label>
                    <select class="form-select" id="availableCourses" size="8">
                        <?php 
                        $prereqIds = array_map(fn($p) => $p['id'], $prerequisites ?? []);
                        ?>
                        <?php foreach ($allCourses as $c): ?>
                            <?php if ($c->id !== $course->id && !in_array($c->id, $prereqIds)): ?>
                                <option value="<?= $c->id ?>" data-code="<?= htmlspecialchars($c->code) ?>" data-name="<?= htmlspecialchars($c->name_bg) ?>">
                                    <?= htmlspecialchars($c->code . ' - ' . $c->name_bg) ?>
                                </option>
                            <?php endif; ?>
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
                    <label class="form-label">Текущи пререквизити</label>
                    <select class="form-select" id="currentPrereqs" name="prerequisites[]" multiple size="8">
                        <?php foreach ($prerequisites ?? [] as $prereq): ?>
                            <option value="<?= $prereq['id'] ?>" selected data-code="<?= htmlspecialchars($prereq['code']) ?>" data-name="<?= htmlspecialchars($prereq['name_bg']) ?>">
                                <?= htmlspecialchars($prereq['code'] . ' - ' . $prereq['name_bg']) ?>
                                <?= $prereq['is_recommended'] ? '(препоръчителен)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div id="cycleWarning" class="alert alert-danger mt-3" style="display: none;">
                <i class="bi bi-exclamation-triangle"></i> 
                <strong>Внимание!</strong> Добавянето на този пререквизит ще създаде цикъл в зависимостите!
            </div>
            
            <hr class="mt-4">
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази промените
            </button>
        </form>
    </div>
</div>

<script>
// Current course ID
const currentCourseId = <?= $course->id ?>;

// Prerequisite graph for all courses (for cycle detection)
const prerequisiteGraph = <?= json_encode($prerequisiteGraph ?? []) ?>;

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

// Check if adding newPrereqId as a prerequisite of currentCourseId would create a cycle
function wouldCreateCycle(newPrereqId) {
    // If currentCourseId is reachable from newPrereqId through the prerequisite chain, it's a cycle
    // DFS from newPrereqId to see if we can reach currentCourseId
    const visited = new Set();
    
    function dfs(courseId) {
        if (courseId === currentCourseId) return true;
        if (visited.has(courseId)) return false;
        
        visited.add(courseId);
        const prereqs = prerequisiteGraph[courseId] || [];
        
        for (const prereqId of prereqs) {
            if (dfs(prereqId)) return true;
        }
        
        return false;
    }
    
    // Check if currentCourseId can be reached from newPrereqId
    return dfs(newPrereqId);
}

// Add prerequisite
document.getElementById('addPrereq').addEventListener('click', function() {
    const available = document.getElementById('availableCourses');
    const current = document.getElementById('currentPrereqs');
    
    const selected = available.selectedOptions;
    if (selected.length === 0) return;
    
    const newPrereqId = parseInt(selected[0].value);
    
    // Check for cycles before adding
    if (wouldCreateCycle(newPrereqId)) {
        document.getElementById('cycleWarning').style.display = 'block';
        setTimeout(() => {
            document.getElementById('cycleWarning').style.display = 'none';
        }, 5000);
        return;
    }
    
    // Move selected options
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
        // Insert in sorted order by code
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
document.getElementById('courseEditForm').addEventListener('submit', function(e) {
    const current = document.getElementById('currentPrereqs');
    Array.from(current.options).forEach(option => {
        option.selected = true;
    });
});

// Initialize on page load
document.addEventListener('DOMContentLoaded', toggleMandatoryFields);
</script>
