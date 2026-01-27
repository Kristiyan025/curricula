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
        <form method="POST" action="/admin/courses/<?= $course->id ?>/edit">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">Код <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code"
                           value="<?= htmlspecialchars($course->code) ?>" required maxlength="20">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_bg" class="form-label">Име (БГ) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_bg" name="name_bg"
                           value="<?= htmlspecialchars($course->name_bg) ?>" required maxlength="200">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_en" class="form-label">Име (EN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_en" name="name_en"
                           value="<?= htmlspecialchars($course->name_en) ?>" required maxlength="200">
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
                    <select class="form-select" id="course_type" name="course_type" required>
                        <option value="MANDATORY" <?= $course->course_type === 'MANDATORY' ? 'selected' : '' ?>>
                            Задължителна
                        </option>
                        <option value="ELECTIVE" <?= $course->course_type === 'ELECTIVE' ? 'selected' : '' ?>>
                            Избираема
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="lecture_hours" class="form-label">Часове лекции/седм.</label>
                    <input type="number" class="form-control" id="lecture_hours" name="lecture_hours"
                           value="<?= $course->lecture_hours ?>" min="0" max="10">
                </div>
                
                <div class="col-md-3 mb-3">
                    <label for="exercise_hours" class="form-label">Часове упражнения/седм.</label>
                    <input type="number" class="form-control" id="exercise_hours" name="exercise_hours"
                           value="<?= $course->exercise_hours ?>" min="0" max="10">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Описание</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($course->description ?? '') ?></textarea>
            </div>
            
            <hr>
            
            <h5>Изисквания</h5>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="requires_lab" name="requires_lab" value="1"
                               <?= $course->requires_lab ? 'checked' : '' ?>>
                        <label class="form-check-label" for="requires_lab">
                            Изисква компютърна зала
                        </label>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="min_room_capacity" class="form-label">Мин. капацитет на зала</label>
                    <input type="number" class="form-control" id="min_room_capacity" name="min_room_capacity"
                           value="<?= $course->min_room_capacity ?? '' ?>" min="0" max="500">
                </div>
            </div>
            
            <div class="mb-3">
                <label for="prerequisites" class="form-label">Предварителни изисквания</label>
                <select class="form-select" id="prerequisites" name="prerequisites[]" multiple size="5">
                    <?php 
                    $prereqIds = array_map(fn($p) => $p['id'], $prerequisites ?? []);
                    ?>
                    <?php foreach ($allCourses ?? [] as $c): ?>
                        <?php if ($c['id'] !== $course['id']): ?>
                            <option value="<?= $c['id'] ?>" <?= in_array($c['id'], $prereqIds) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['code'] . ' - ' . $c['name_bg']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Задръжте Ctrl за множествен избор</small>
            </div>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази промените
            </button>
        </form>
    </div>
</div>
