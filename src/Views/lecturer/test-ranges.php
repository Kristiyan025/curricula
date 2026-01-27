<h2><i class="bi bi-calendar-range"></i> Периоди за контролни</h2>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/lecturer">Начало</a></li>
        <li class="breadcrumb-item"><a href="/lecturer/course-instance/<?= $instance['id'] ?>"><?= htmlspecialchars($instance['name_bg']) ?></a></li>
        <li class="breadcrumb-item active">Контролни</li>
    </ol>
</nav>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-list-check"></i> Съществуващи периоди
            </div>
            <div class="card-body">
                <?php if (empty($testRanges)): ?>
                    <p class="text-muted">Няма зададени периоди за контролни.</p>
                <?php else: ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Начало</th>
                                <th>Край</th>
                                <th>Продължителност</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testRanges as $range): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($range['start_date'])) ?></td>
                                    <td><?= date('d.m.Y', strtotime($range['end_date'])) ?></td>
                                    <td><?= $range['duration_minutes'] ?> мин.</td>
                                    <td>
                                        <form method="POST" action="/lecturer/test-ranges/<?= $instance['id'] ?>/delete/<?= $range['id'] ?>"
                                              class="d-inline" onsubmit="return confirm('Сигурни ли сте?')">
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
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-plus-circle"></i> Нов период
            </div>
            <div class="card-body">
                <form method="POST" action="/lecturer/test-ranges/<?= $instance['id'] ?>">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label for="start_date" class="form-label">Начална дата <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="start_date" name="start_date" required
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="end_date" class="form-label">Крайна дата <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="end_date" name="end_date" required
                               min="<?= date('Y-m-d') ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label for="duration_minutes" class="form-label">Продължителност (мин.)</label>
                        <input type="number" class="form-control" id="duration_minutes" name="duration_minutes"
                               value="90" min="30" max="240">
                    </div>
                    
                    <button type="submit" class="btn btn-fmi w-100">
                        <i class="bi bi-save"></i> Добави
                    </button>
                </form>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Информация</h6>
                <p class="small text-muted">
                    Задайте периоди, в които може да се проведе контролно по тази дисциплина.
                    Системата ще генерира автоматично разписание на контролните в рамките на
                    зададените периоди, като избягва конфликти с други дисциплини.
                </p>
            </div>
        </div>
    </div>
</div>
