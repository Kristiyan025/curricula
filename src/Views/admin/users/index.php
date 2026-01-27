<h2><i class="bi bi-people"></i> Потребители</h2>

<div class="row mb-3">
    <div class="col-md-6">
        <a href="/admin/users/create" class="btn btn-fmi">
            <i class="bi bi-person-plus"></i> Нов потребител
        </a>
        <a href="/admin" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Обратно
        </a>
    </div>
    <div class="col-md-6">
        <form method="GET" action="/admin/users" class="d-flex">
            <select class="form-select me-2" name="role" onchange="this.form.submit()">
                <option value="">Всички роли</option>
                <option value="ADMIN" <?= ($selectedRole ?? '') === 'ADMIN' ? 'selected' : '' ?>>Администратори</option>
                <option value="LECTURER" <?= ($selectedRole ?? '') === 'LECTURER' ? 'selected' : '' ?>>Преподаватели</option>
                <option value="ASSISTANT" <?= ($selectedRole ?? '') === 'ASSISTANT' ? 'selected' : '' ?>>Асистенти</option>
                <option value="STUDENT" <?= ($selectedRole ?? '') === 'STUDENT' ? 'selected' : '' ?>>Студенти</option>
            </select>
        </form>
    </div>
</div>

<?php if (empty($users)): ?>
    <div class="alert alert-info">
        Няма намерени потребители.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Имейл</th>
                    <th>Име</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= $u['id'] ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Активен</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Неактивен</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/users/<?= $u['id'] ?>/edit" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="/admin/users/<?= $u['id'] ?>/delete" class="d-inline"
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
