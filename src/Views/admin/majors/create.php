<h2><i class="bi bi-mortarboard"></i> Нова специалност</h2>

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
        <form method="POST" action="/admin/majors/create">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="code" class="form-label">Код <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="code" name="code" 
                           value="<?= htmlspecialchars($old['code'] ?? '') ?>" required maxlength="20">
                    <small class="text-muted">Напр. KN, SI, IS</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_bg" class="form-label">Име (БГ) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_bg" name="name_bg"
                           value="<?= htmlspecialchars($old['name_bg'] ?? '') ?>" required maxlength="200">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="name_en" class="form-label">Име (EN) <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name_en" name="name_en"
                           value="<?= htmlspecialchars($old['name_en'] ?? '') ?>" required maxlength="200">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="degree" class="form-label">Степен <span class="text-danger">*</span></label>
                    <select class="form-select" id="degree" name="degree" required>
                        <option value="">-- Изберете --</option>
                        <option value="BACHELOR" <?= ($old['degree'] ?? '') === 'BACHELOR' ? 'selected' : '' ?>>
                            Бакалавър
                        </option>
                        <option value="MASTER" <?= ($old['degree'] ?? '') === 'MASTER' ? 'selected' : '' ?>>
                            Магистър
                        </option>
                        <option value="PHD" <?= ($old['degree'] ?? '') === 'PHD' ? 'selected' : '' ?>>
                            Докторант
                        </option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="duration_semesters" class="form-label">Продължителност (семестри) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="duration_semesters" name="duration_semesters"
                           value="<?= htmlspecialchars($old['duration_semesters'] ?? '8') ?>" required min="1" max="20">
                </div>
            </div>
            
            <hr>
            
            <h5>Потоци</h5>
            <p class="text-muted small">Добавете потоци към специалността. Всеки поток има групи от студенти.</p>
            
            <div id="streams-container">
                <div class="stream-row mb-3 p-3 bg-light rounded">
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Номер на поток</label>
                            <input type="number" class="form-control" name="streams[0][number]" value="1" min="1">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Брой групи</label>
                            <input type="number" class="form-control" name="streams[0][group_count]" value="4" min="1">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="form-label">Курс</label>
                            <input type="number" class="form-control" name="streams[0][year]" value="1" min="1" max="6">
                        </div>
                    </div>
                </div>
            </div>
            
            <button type="button" class="btn btn-outline-secondary mb-3" onclick="addStream()">
                <i class="bi bi-plus-circle"></i> Добави поток
            </button>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази
            </button>
        </form>
    </div>
</div>

<script>
let streamIndex = 1;

function addStream() {
    const container = document.getElementById('streams-container');
    const html = `
        <div class="stream-row mb-3 p-3 bg-light rounded">
            <div class="row">
                <div class="col-md-4 mb-2">
                    <label class="form-label">Номер на поток</label>
                    <input type="number" class="form-control" name="streams[${streamIndex}][number]" value="${streamIndex + 1}" min="1">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Брой групи</label>
                    <input type="number" class="form-control" name="streams[${streamIndex}][group_count]" value="4" min="1">
                </div>
                <div class="col-md-4 mb-2">
                    <label class="form-label">Курс</label>
                    <input type="number" class="form-control" name="streams[${streamIndex}][year]" value="1" min="1" max="6">
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.parentElement.remove()">
                <i class="bi bi-trash"></i> Премахни
            </button>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', html);
    streamIndex++;
}
</script>
