<h2><i class="bi bi-bookmark-star"></i> Избираеми дисциплини</h2>

<p class="text-muted">
    Учебна година <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
    <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
</p>

<a href="/student" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Обратно
</a>

<?php if (empty($electives)): ?>
    <div class="alert alert-info">
        Няма налични избираеми дисциплини за този семестър.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($electives as $e): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header">
                        <strong><?= htmlspecialchars($e['name_bg']) ?></strong>
                        <span class="badge bg-secondary float-end"><?= $e['credits'] ?> кредита</span>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small"><?= htmlspecialchars($e['code']) ?></p>
                        <p><?= htmlspecialchars($e['description'] ?? 'Няма описание.') ?></p>
                        <p class="mb-1">
                            <i class="bi bi-people"></i> Записани: <?= $e['enrolled_count'] ?? 0 ?> / 50
                        </p>
                    </div>
                    <div class="card-footer">
                        <?php if ($e['my_enrollment']): ?>
                            <span class="text-success me-2">
                                <i class="bi bi-check-circle-fill"></i> Записани сте
                            </span>
                            <form method="POST" action="/student/unenroll/<?= $e['id'] ?>" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                        onclick="return confirm('Сигурни ли сте, че искате да се отпишете?')">
                                    <i class="bi bi-x-circle"></i> Отпиши се
                                </button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/student/enroll/<?= $e['id'] ?>">
                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                <button type="submit" class="btn btn-fmi btn-sm">
                                    <i class="bi bi-plus-circle"></i> Запиши се
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
