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
                <li class="breadcrumb-item"><a href="/schedule/group">Разписания по група</a></li>
                <?php if ($group ?? null): ?>
                    <li class="breadcrumb-item active">Група <?= $group['number'] ?></li>
                <?php endif; ?>
            </ol>
        </nav>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <h2>
            <i class="bi bi-people"></i>
            Разписание на група
            <?php if ($group ?? null): ?>
                <?= $group['number'] ?>
            <?php endif; ?>
        </h2>
        <?php if ($stream ?? null): ?>
            <p class="text-muted">
                <?= htmlspecialchars($major['name_bg'] ?? '') ?> - <?= htmlspecialchars($stream['name_bg'] ?? '') ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="col-md-4 text-end">
        <div class="btn-group">
            <a href="?group_id=<?= $group['id'] ?? '' ?>&view=table" 
               class="btn <?= ($view ?? 'table') === 'table' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                <i class="bi bi-table"></i> Таблица
            </a>
            <a href="?group_id=<?= $group['id'] ?? '' ?>&view=calendar" 
               class="btn <?= ($view ?? '') === 'calendar' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                <i class="bi bi-calendar3"></i> Календар
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" action="/schedule/group">
            <label for="groupSelect" class="form-label">Изберете група:</label>
            <select class="form-select" id="groupSelect" name="group_id" onchange="this.form.submit()">
                <option value="">-- Изберете --</option>
                <?php foreach ($majors ?? [] as $m): ?>
                    <?php 
                    $mStreams = \App\Models\MajorStream::where('major_id', $m['id']);
                    foreach ($mStreams as $s): 
                        $groups = \App\Models\StudentGroup::where('stream_id', $s['id']);
                    ?>
                        <optgroup label="<?= htmlspecialchars($m['name_bg']) ?> - <?= htmlspecialchars($s['name']) ?>">
                            <?php foreach ($groups as $g): ?>
                                <option value="<?= $g['id'] ?>" <?= ($group ?? null) && $group['id'] == $g['id'] ? 'selected' : '' ?>>
                                    Група <?= $g['number'] ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (empty($schedule)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <?php if (!($group ?? null)): ?>
            Моля, изберете група, за да видите разписанието.
        <?php else: ?>
            Няма генерирано разписание за тази група.
        <?php endif; ?>
    </div>
<?php else: ?>
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
                                ?>
                                    <div class="schedule-slot <?= $slotClass ?>">
                                        <strong><?= htmlspecialchars($slot['course_name'] ?? 'N/A') ?></strong>
                                        <br>
                                        <small>
                                            <?= $slot['slot_type'] === 'EXERCISE' ? 'Упр.' : 'Лекция' ?>
                                            | <?= htmlspecialchars($slot['room_number'] ?? '') ?>
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
    
    <div class="mt-4">
        <h6>Легенда:</h6>
        <span class="schedule-slot slot-lecture d-inline-block me-3">Лекция</span>
        <span class="schedule-slot slot-exercise d-inline-block">Упражнение</span>
    </div>
<?php endif; ?>
