<h2><i class="bi bi-mortarboard"></i> Специалности</h2>

<div class="mb-3">
    <a href="/admin/majors/create" class="btn btn-fmi">
        <i class="bi bi-plus-circle"></i> Нова специалност
    </a>
    <a href="/admin" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Обратно
    </a>
</div>

<?php if (empty($majors)): ?>
    <div class="alert alert-info">
        Няма създадени специалности.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Код</th>
                    <th>Име (БГ)</th>
                    <th>Име (EN)</th>
                    <th>Степен</th>
                    <th>Потоци</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($majors as $major): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($major->code) ?></code></td>
                        <td><?= htmlspecialchars($major->name_bg) ?></td>
                        <td><?= htmlspecialchars($major->name_en) ?></td>
                        <td>
                            <span class="badge bg-<?= $major->degree === 'BACHELOR' ? 'primary' : ($major->degree === 'MASTER' ? 'success' : 'info') ?>">
                                <?= $major->degree === 'BACHELOR' ? 'Бакалавър' : ($major->degree === 'MASTER' ? 'Магистър' : 'Докторант') ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            $streams = $major->streams ?? [];
                            echo count($streams);
                            ?>
                        </td>
                        <td>
                            <a href="/admin/majors/<?= $major->id ?>/edit" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="/admin/majors/<?= $major->id ?>/delete" class="d-inline"
                                  onsubmit="return confirm('Сигурни ли сте?')">
                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
