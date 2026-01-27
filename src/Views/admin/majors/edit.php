<h2><i class="bi bi-mortarboard"></i> Редактиране на специалност</h2>

<a href="/admin/majors" class="btn btn-secondary mb-3">
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
        <form method="POST" action="/admin/majors/<?= $major->id ?>/edit">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">Код <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" 
                           value="<?= htmlspecialchars($major->code) ?>" required maxlength="20">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_bg" class="form-label">Име (БГ) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_bg" name="name_bg"
                           value="<?= htmlspecialchars($major->name_bg) ?>" required maxlength="200">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_en" class="form-label">Име (EN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_en" name="name_en"
                           value="<?= htmlspecialchars($major->name_en) ?>" required maxlength="200">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="degree" class="form-label">Степен <span class="text-danger">*</span></label>
                    <select class="form-select" id="degree" name="degree" required>
                        <option value="BACHELOR" <?= $major->degree === 'BACHELOR' ? 'selected' : '' ?>>
                            Бакалавър
                        </option>
                        <option value="MASTER" <?= $major->degree === 'MASTER' ? 'selected' : '' ?>>
                            Магистър
                        </option>
                        <option value="PHD" <?= $major->degree === 'PHD' ? 'selected' : '' ?>>
                            Докторант
                        </option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="duration_semesters" class="form-label">Продължителност (семестри) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="duration_semesters" name="duration_semesters"
                           value="<?= $major->duration_semesters ?>" required min="1" max="20">
                </div>
            </div>
            
            <hr>
            
            <h5>Потоци</h5>
            
            <?php $streams = $major->streams ?? []; ?>
            
            <?php if (!empty($streams)): ?>
                <div class="table-responsive mb-3">
                    <table class="table table-sm">
                        <thead class="table-secondary">
                            <tr>
                                <th>Номер</th>
                                <th>Курс</th>
                                <th>Групи</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($streams as $stream): ?>
                                <tr>
                                    <td>Поток <?= $stream->stream_number ?></td>
                                    <td><?= $stream->year ?> курс</td>
                                    <td>
                                        <?php 
                                        $groups = $stream->groups ?? [];
                                        echo count($groups) . ' групи';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <p class="text-muted small">
                За управление на потоци и групи, моля използвайте специализираните функции.
            </p>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази промените
            </button>
        </form>
    </div>
</div>
