<?php
$days = [
    'MON' => 'Понеделник',
    'TUE' => 'Вторник', 
    'WED' => 'Сряда',
    'THU' => 'Четвъртък',
    'FRI' => 'Петък',
    'SAT' => 'Събота'
];

$timeSlots = [];
for ($h = 8; $h < 20; $h++) {
    $timeSlots[] = sprintf('%02d:00', $h);
}

// Group schedule by day and time
$byDayTime = [];
foreach ($schedule ?? [] as $slot) {
    $day = $slot['day_of_week'];
    $time = substr($slot['start_time'], 0, 5);
    $byDayTime[$day][$time][] = $slot;
}
?>

<div class="row mb-4">
    <div class="col">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="/">Начало</a></li>
                <li class="breadcrumb-item"><a href="/schedule/stream">Разписания</a></li>
                <?php if ($major ?? null): ?>
                    <li class="breadcrumb-item"><?= htmlspecialchars($major['name_bg'] ?? '') ?></li>
                <?php endif; ?>
                <?php if ($stream ?? null): ?>
                    <li class="breadcrumb-item active"><?= htmlspecialchars($stream['name_bg'] ?? '') ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>
            <i class="bi bi-calendar-week"></i>
            Седмично разписание
            <?php if ($stream ?? null): ?>
                - <?= htmlspecialchars($stream['name_bg'] ?? '') ?>
            <?php endif; ?>
        </h2>
        <?php if ($period ?? null): ?>
            <p class="text-muted">
                Учебна година <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
                <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
            </p>
        <?php endif; ?>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="?stream_id=<?= $stream['id'] ?? '' ?>&view=table" 
               class="btn <?= ($view ?? 'table') === 'table' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                <i class="bi bi-table"></i> Таблица
            </a>
            <a href="?stream_id=<?= $stream['id'] ?? '' ?>&view=calendar" 
               class="btn <?= ($view ?? '') === 'calendar' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar3"></i> Календар
            </a>
        </div>
    </div>
</div>

<!-- Stream/Group selector -->
<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" action="/schedule/stream">
            <label for="streamSelect" class="form-label">Изберете поток:</label>
            <select class="form-select" id="streamSelect" name="stream_id" onchange="this.form.submit()">
                <option value="">-- Изберете --</option>
                <?php foreach ($majors ?? [] as $m): ?>
                    <optgroup label="<?= htmlspecialchars($m['name_bg'] ?? '') ?>">
                        <?php 
                        $mStreams = \App\Models\MajorStream::where('major_id', $m['id']);
                        foreach ($mStreams as $s): 
                        ?>
                            <option value="<?= $s['id'] ?>" <?= ($stream ?? null) && $stream['id'] == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name_bg'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (empty($schedule)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <?php if (!($stream ?? null)): ?>
            Моля, изберете поток, за да видите разписанието.
        <?php else: ?>
            Няма генерирано разписание за този поток.
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php if (($view ?? 'table') === 'table'): ?>
        <!-- Table view -->
        <div class="table-responsive">
            <table class="table table-bordered schedule-table">
                <thead>
                    <tr>
                        <th style="width: 80px">Час</th>
                        <?php foreach ($days as $code => $name): ?>
                            <th><?= $name ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($timeSlots as $time): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $time ?></td>
                            <?php foreach (array_keys($days) as $day): ?>
                                <td>
                                    <?php 
                                    $slots = $byDayTime[$day][$time] ?? [];
                                    foreach ($slots as $slot): 
                                        $slotClass = 'slot-' . strtolower($slot['slot_type'] ?? 'lecture');
                                        if (!empty($slot['is_elective'])) $slotClass = 'slot-elective';
                                    ?>
                                        <div class="schedule-slot <?= $slotClass ?>">
                                            <strong><?= htmlspecialchars($slot['course_name'] ?? 'N/A') ?></strong>
                                            <br>
                                            <small>
                                                <?= $slot['slot_type'] === 'EXERCISE' ? 'Упр.' : 'Лекция' ?>
                                                | <?= htmlspecialchars($slot['room_number'] ?? '') ?>
                                                <?php if (!empty($slot['group_number'])): ?>
                                                    | Гр. <?= $slot['group_number'] ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <!-- Calendar view (simplified) -->
        <div class="row">
            <?php foreach ($days as $dayCode => $dayName): ?>
                <div class="col-md-2 mb-3">
                    <div class="card">
                        <div class="card-header card-header-fmi text-center">
                            <?= $dayName ?>
                        </div>
                        <div class="card-body p-2">
                            <?php 
                            $daySlots = [];
                            foreach ($schedule as $slot) {
                                if ($slot['day_of_week'] === $dayCode) {
                                    $daySlots[] = $slot;
                                }
                            }
                            usort($daySlots, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
                            
                            foreach ($daySlots as $slot):
                                $slotClass = 'slot-' . strtolower($slot['slot_type'] ?? 'lecture');
                            ?>
                                <div class="schedule-slot <?= $slotClass ?>">
                                    <strong><?= substr($slot['start_time'], 0, 5) ?></strong>
                                    <br>
                                    <?= htmlspecialchars($slot['course_name'] ?? '') ?>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($slot['room_number'] ?? '') ?></small>
                                </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($daySlots)): ?>
                                <p class="text-muted text-center mb-0">Няма занятия</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Legend -->
    <div class="mt-4">
        <h6>Легенда:</h6>
        <span class="schedule-slot slot-lecture d-inline-block me-3">Лекция</span>
        <span class="schedule-slot slot-exercise d-inline-block me-3">Упражнение</span>
        <span class="schedule-slot slot-elective d-inline-block">Избираема</span>
    </div>
<?php endif; ?>
