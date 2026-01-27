<h2><i class="bi bi-gear"></i> Управление на дисциплина</h2>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/lecturer">Начало</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($instance['name_bg']) ?></li>
    </ol>
</nav>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-info-circle"></i> Информация
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th width="200">Код:</th>
                        <td><?= htmlspecialchars($instance['code']) ?></td>
                    </tr>
                    <tr>
                        <th>Име:</th>
                        <td><?= htmlspecialchars($instance['name_bg']) ?></td>
                    </tr>
                    <tr>
                        <th>Специалност:</th>
                        <td><?= htmlspecialchars($instance['major_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Поток:</th>
                        <td><?= $instance['stream_number'] ?></td>
                    </tr>
                    <tr>
                        <th>Часове лекции:</th>
                        <td><?= $instance['lecture_hours'] ?></td>
                    </tr>
                    <tr>
                        <th>Часове упражнения:</th>
                        <td><?= $instance['exercise_hours'] ?></td>
                    </tr>
                    <tr>
                        <th>Кредити:</th>
                        <td><?= $instance['credits'] ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-calendar-week"></i> Разписание
            </div>
            <div class="card-body">
                <?php if (empty($schedule)): ?>
                    <p class="text-muted">Няма генерирано разписание.</p>
                <?php else: ?>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Ден</th>
                                <th>Час</th>
                                <th>Зала</th>
                                <th>Тип</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $days = ['MON'=>'Пон', 'TUE'=>'Вт', 'WED'=>'Ср', 'THU'=>'Чет', 'FRI'=>'Пет'];
                            foreach ($schedule as $slot): 
                            ?>
                                <tr>
                                    <td><?= $days[$slot['day_of_week']] ?? $slot['day_of_week'] ?></td>
                                    <td><?= substr($slot['start_time'], 0, 5) ?> - <?= substr($slot['end_time'], 0, 5) ?></td>
                                    <td><?= htmlspecialchars($slot['room_number']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $slot['slot_type'] === 'LECTURE' ? 'primary' : 'success' ?>">
                                            <?= $slot['slot_type'] === 'LECTURE' ? 'Лекция' : 'Упражнение' ?>
                                        </span>
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
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-people"></i> Асистенти
            </div>
            <div class="card-body">
                <?php if (empty($assistants)): ?>
                    <p class="text-muted">Няма назначени асистенти.</p>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach ($assistants as $assistant): ?>
                            <li class="mb-2">
                                <i class="bi bi-person"></i>
                                <?= htmlspecialchars($assistant['name']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-tools"></i> Действия
            </div>
            <div class="card-body">
                <a href="/lecturer/test-ranges/<?= $instance['id'] ?>" class="btn btn-fmi w-100 mb-2">
                    <i class="bi bi-calendar-range"></i> Периоди за контролни
                </a>
                <a href="/lecturer/preferences" class="btn btn-outline-primary w-100">
                    <i class="bi bi-sliders"></i> Предпочитания
                </a>
            </div>
        </div>
    </div>
</div>
