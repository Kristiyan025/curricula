<h2><i class="bi bi-calendar-plus"></i> Генериране на разписания</h2>

<p class="text-muted">
    Текущ период: <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
    <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
</p>

<div class="row">
    <!-- Weekly Schedule -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-calendar-week"></i> Седмично разписание
            </div>
            <div class="card-body">
                <?php if (!empty($weeklyVariants)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Вариант</th>
                                <th>Фитнес</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($weeklyVariants as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v->name) ?></td>
                                    <td><?= number_format($v->fitness_score, 4) ?></td>
                                    <td>
                                        <?php if ($v->is_selected): ?>
                                            <span class="badge bg-success">Избран</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Чакащ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/schedule/view/<?= $v->id ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!$v->is_selected): ?>
                                            <form method="POST" action="/admin/schedule/select/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Избери</button>
                                            </form>
                                            <form method="POST" action="/admin/schedule/delete/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Изтрий</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Няма генерирани варианти.</p>
                <?php endif; ?>
                
                <form method="POST" action="/admin/schedule/generate/weekly">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    <button type="submit" class="btn btn-fmi">
                        <i class="bi bi-play-circle"></i> Генерирай нови варианти
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Test Schedule -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-pencil-square"></i> Контролни
            </div>
            <div class="card-body">
                <?php if (!empty($testVariants)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Вариант</th>
                                <th>Фитнес</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($testVariants as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v->name) ?></td>
                                    <td><?= number_format($v->fitness_score, 4) ?></td>
                                    <td>
                                        <?php if ($v->is_selected): ?>
                                            <span class="badge bg-success">Избран</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Чакащ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/schedule/view/<?= $v->id ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!$v->is_selected): ?>
                                            <form method="POST" action="/admin/schedule/select/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Избери</button>
                                            </form>
                                            <form method="POST" action="/admin/schedule/delete/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Изтрий</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Няма генерирани варианти.</p>
                <?php endif; ?>
                
                <form method="POST" action="/admin/schedule/generate/tests">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    <button type="submit" class="btn btn-fmi">
                        <i class="bi bi-play-circle"></i> Генерирай
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Exam Schedule - Regular -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-journal-check"></i> Редовна сесия
            </div>
            <div class="card-body">
                <?php if (!empty($examRegularVariants)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Вариант</th>
                                <th>Фитнес</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examRegularVariants as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v->name) ?></td>
                                    <td><?= number_format($v->fitness_score, 4) ?></td>
                                    <td>
                                        <?php if ($v->is_selected): ?>
                                            <span class="badge bg-success">Избран</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Чакащ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/schedule/view/<?= $v->id ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!$v->is_selected): ?>
                                            <form method="POST" action="/admin/schedule/select/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Избери</button>
                                            </form>
                                            <form method="POST" action="/admin/schedule/delete/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Изтрий</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Няма генерирани варианти.</p>
                <?php endif; ?>
                
                <form method="POST" action="/admin/schedule/generate/exams">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    <input type="hidden" name="session_type" value="REGULAR">
                    <button type="submit" class="btn btn-fmi">
                        <i class="bi bi-play-circle"></i> Генерирай
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Exam Schedule - Liquidation -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-journal-x"></i> Ликвидационна сесия
            </div>
            <div class="card-body">
                <?php if (!empty($examLiquidationVariants)): ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Вариант</th>
                                <th>Фитнес</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($examLiquidationVariants as $v): ?>
                                <tr>
                                    <td><?= htmlspecialchars($v->name) ?></td>
                                    <td><?= number_format($v->fitness_score, 4) ?></td>
                                    <td>
                                        <?php if ($v->is_selected): ?>
                                            <span class="badge bg-success">Избран</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Чакащ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/schedule/view/<?= $v->id ?>" class="btn btn-sm btn-info">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <?php if (!$v->is_selected): ?>
                                            <form method="POST" action="/admin/schedule/select/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-success">Избери</button>
                                            </form>
                                            <form method="POST" action="/admin/schedule/delete/<?= $v->id ?>" class="d-inline">
                                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">Изтрий</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Няма генерирани варианти.</p>
                <?php endif; ?>
                
                <form method="POST" action="/admin/schedule/generate/exams">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    <input type="hidden" name="session_type" value="LIQUIDATION">
                    <button type="submit" class="btn btn-fmi">
                        <i class="bi bi-play-circle"></i> Генерирай
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle"></i>
    <strong>Забележка:</strong> Генерирането на разписания може да отнеме до 20 минути в зависимост от броя на курсовете и групите.
    Системата ще генерира до 3 варианта, от които можете да изберете най-подходящия.
</div>

<a href="/admin" class="btn btn-secondary">
    <i class="bi bi-arrow-left"></i> Обратно
</a>
