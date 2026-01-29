<h2><i class="bi bi-door-open"></i> Редактиране на зала</h2>

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
        <form method="POST" action="/admin/rooms/<?= $room->id ?>/edit">
            <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="number" class="form-label">Номер <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="number" name="number"
                           value="<?= htmlspecialchars($room->number ?? '') ?>" required maxlength="3">
                    <small class="text-muted">Трицифрен код (първа цифра = етаж)</small>
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="floor" class="form-label">Етаж <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="floor" name="floor"
                           value="<?= $room->floor ?? '' ?>" required min="1" max="6">
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="white_boards" class="form-label">Бели дъски</label>
                    <input type="number" class="form-control" id="white_boards" name="white_boards"
                           value="<?= $room->white_boards ?? 0 ?>" min="0">
                </div>
                
                <div class="col-md-4 mb-3">
                    <label for="black_boards" class="form-label">Черни дъски</label>
                    <input type="number" class="form-control" id="black_boards" name="black_boards"
                           value="<?= $room->black_boards ?? 0 ?>" min="0">
                </div>
            </div>
            
            <hr>
            
            <button type="submit" class="btn btn-fmi">
                <i class="bi bi-save"></i> Запази промените
            </button>
        </form>
    </div>
</div>
