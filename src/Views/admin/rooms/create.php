<h2><i class="bi bi-door-open"></i> Нова зала</h2>

<a href="/admin/rooms" class="btn btn-secondary mb-3">
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
        <form method="POST" action="/admin/rooms/create">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="room_number" class="form-label">Номер <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="room_number" name="room_number"
                           value="<?= htmlspecialchars($old['room_number'] ?? '') ?>" required maxlength="20">
                    <small class="text-muted">Напр. 200, 310A, КЗ-1</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="room_type" class="form-label">Тип <span class="text-danger">*</span></label>
                    <select class="form-select" id="room_type" name="room_type" required>
                        <option value="">-- Изберете --</option>
                        <option value="LECTURE_HALL" <?= ($old['room_type'] ?? '') === 'LECTURE_HALL' ? 'selected' : '' ?>>
                            Лекционна зала
                        </option>
                        <option value="COMPUTER_LAB" <?= ($old['room_type'] ?? '') === 'COMPUTER_LAB' ? 'selected' : '' ?>>
                            Компютърна зала
                        </option>
                        <option value="SEMINAR_ROOM" <?= ($old['room_type'] ?? '') === 'SEMINAR_ROOM' ? 'selected' : '' ?>>
                            Семинарна зала
                        </option>
                    </select>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="capacity" class="form-label">Капацитет <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="capacity" name="capacity"
                           value="<?= htmlspecialchars($old['capacity'] ?? '30') ?>" required min="1" max="500">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="building" class="form-label">Сграда</label>
                    <input type="text" class="form-control" id="building" name="building"
                           value="<?= htmlspecialchars($old['building'] ?? 'ФМИ') ?>">
                </div>
                
                <div class="col-md-6 mb-3">
                    <label for="floor" class="form-label">Етаж</label>
                    <input type="number" class="form-control" id="floor" name="floor"
                           value="<?= htmlspecialchars($old['floor'] ?? '') ?>">
                </div>
            </div>
            
            <hr>
            
            <h5>Оборудване</h5>
            
            <div class="row">
                <div class="col-md-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_projector" name="has_projector" value="1"
                               <?= !empty($old['has_projector']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="has_projector">
                            <i class="bi bi-tv"></i> Проектор
                        </label>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_computers" name="has_computers" value="1"
                               <?= !empty($old['has_computers']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="has_computers">
                            <i class="bi bi-pc-display"></i> Компютри
                        </label>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="has_whiteboard" name="has_whiteboard" value="1"
                               <?= !empty($old['has_whiteboard']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="has_whiteboard">
                            <i class="bi bi-easel"></i> Бяла дъска
                        </label>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="is_accessible" name="is_accessible" value="1"
                               <?= !empty($old['is_accessible']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="is_accessible">
                            <i class="bi bi-universal-access"></i> Достъпна
                        </label>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Създай
            </button>
        </form>
    </div>
</div>
