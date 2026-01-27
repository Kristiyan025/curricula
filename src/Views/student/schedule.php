<h2><i class="bi bi-calendar-week"></i> Моето пълно разписание</h2>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/student">Начало</a></li>
        <li class="breadcrumb-item active">Разписание</li>
    </ol>
</nav>

<div class="row mb-3">
    <div class="col">
        <p class="text-muted">
            <?= htmlspecialchars($student['major_name'] ?? '') ?> - 
            <?= htmlspecialchars($student['stream_name'] ?? '') ?>,
            Група <?= $student['group_number'] ?? '' ?>
        </p>
    </div>
    <div class="col text-end">
        <button class="btn btn-outline-secondary btn-sm" onclick="printSchedule()">
            <i class="bi bi-printer"></i> Печат
        </button>
    </div>
</div>

<?php if (empty($schedule)): ?>
    <div class="alert alert-warning">
        Няма генерирано разписание.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered schedule-table mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th width="80">Час</th>
                            <th>Понеделник</th>
                            <th>Вторник</th>
                            <th>Сряда</th>
                            <th>Четвъртък</th>
                            <th>Петък</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $timeSlots = [
                            ['08:00', '10:00'], ['10:00', '12:00'], ['12:00', '14:00'],
                            ['14:00', '16:00'], ['16:00', '18:00'], ['18:00', '20:00']
                        ];
                        $days = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
                        
                        $byDayTime = [];
                        foreach ($schedule as $slot) {
                            $key = $slot['day_of_week'] . '_' . substr($slot['start_time'], 0, 5);
                            $byDayTime[$key] = $slot;
                        }
                        ?>
                        <?php foreach ($timeSlots as $ts): ?>
                            <tr>
                                <td class="text-center">
                                    <strong><?= $ts[0] ?></strong><br>
                                    <small><?= $ts[1] ?></small>
                                </td>
                                <?php foreach ($days as $day): ?>
                                    <?php 
                                    $key = $day . '_' . $ts[0];
                                    $slot = $byDayTime[$key] ?? null;
                                    ?>
                                    <td class="<?= $slot ? 'slot-' . strtolower($slot['slot_type']) : '' ?>">
                                        <?php if ($slot): ?>
                                            <strong><?= htmlspecialchars($slot['course_name'] ?? '') ?></strong>
                                            <br>
                                            <small>
                                                <?= $slot['slot_type'] === 'LECTURE' ? 'Лекция' : 'Упражнение' ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-door-open"></i> <?= htmlspecialchars($slot['room_number'] ?? '') ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-person"></i> <?= htmlspecialchars($slot['lecturer_name'] ?? '') ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="mt-3">
        <span class="badge slot-lecture p-2 me-2">Лекция</span>
        <span class="badge slot-exercise p-2">Упражнение</span>
    </div>
<?php endif; ?>

<hr>

<h4><i class="bi bi-pencil-square"></i> Предстоящи контролни</h4>

<?php if (empty($tests)): ?>
    <div class="alert alert-info">
        Няма насрочени контролни.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Дата</th>
                    <th>Час</th>
                    <th>Дисциплина</th>
                    <th>Зала</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tests as $test): ?>
                    <tr class="<?= $test['test_date'] < date('Y-m-d') ? 'text-muted' : '' ?>">
                        <td><?= date('d.m.Y', strtotime($test['test_date'])) ?></td>
                        <td><?= substr($test['start_time'], 0, 5) ?></td>
                        <td><?= htmlspecialchars($test['course_name'] ?? '') ?></td>
                        <td><?= htmlspecialchars($test['room_number'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
