<h2><i class="bi bi-chalkboard-teacher"></i> Моите дисциплини</h2>

<a href="/lecturer" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Обратно
</a>

<p class="text-muted">
    Учебна година <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
    <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
</p>

<?php if (empty($instances)): ?>
    <div class="alert alert-info">
        Нямате възложени дисциплини за този семестър.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($instances as $instance): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-fmi">
                        <strong><?= htmlspecialchars($instance['name_bg']) ?></strong>
                        <span class="badge bg-light text-dark float-end"><?= htmlspecialchars($instance['code']) ?></span>
                    </div>
                    <div class="card-body">
                        <p>
                            <i class="bi bi-mortarboard"></i>
                            <?= htmlspecialchars($instance['major_name']) ?> -
                            Поток <?= $instance['stream_number'] ?>
                        </p>
                        <p>
                            <i class="bi bi-clock"></i>
                            <?= $instance['lecture_hours'] ?>ч лекции / <?= $instance['exercise_hours'] ?>ч упражнения
                        </p>
                        
                        <?php if (!empty($instance['assistants'])): ?>
                            <p class="mb-1"><strong>Асистенти:</strong></p>
                            <ul class="list-unstyled small">
                                <?php foreach ($instance['assistants'] as $assistant): ?>
                                    <li>
                                        <i class="bi bi-person"></i>
                                        <?= htmlspecialchars($assistant['name']) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="/lecturer/course-instance/<?= $instance['id'] ?>" class="btn btn-fmi btn-sm">
                            <i class="bi bi-gear"></i> Управление
                        </a>
                        <a href="/lecturer/test-ranges/<?= $instance['id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-calendar-range"></i> Контролни
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
