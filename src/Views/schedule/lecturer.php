<h2><i class="bi bi-calendar-week"></i> Разписание на преподавател</h2>

<div class="row mb-4">
    <div class="col-md-6">
        <form method="GET" action="/schedule/lecturer" class="d-flex">
            <select class="form-select me-2" name="lecturer_id" onchange="this.form.submit()">
                <option value="">-- Изберете преподавател --</option>
                <?php foreach ($lecturers ?? [] as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= ($selectedLecturer ?? '') == $l['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars(($l['title'] ? $l['title'] . ' ' : '') . $l['first_name'] . ' ' . $l['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <div class="col-md-6 text-end">
        <a href="/schedule/stream" class="btn btn-outline-secondary">По поток</a>
        <a href="/schedule/room" class="btn btn-outline-secondary">По зала</a>
    </div>
</div>

<?php if (empty($selectedLecturer)): ?>
    <div class="alert alert-info">
        Моля, изберете преподавател от списъка.
    </div>
<?php elseif (empty($schedule)): ?>
    <div class="alert alert-warning">
        Няма генерирано разписание за избрания преподавател.
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-header card-header-fmi">
            <i class="bi bi-person"></i> 
            <?= htmlspecialchars($lecturerInfo['title'] ?? '') ?>
            <?= htmlspecialchars($lecturerInfo['first_name'] . ' ' . $lecturerInfo['last_name']) ?>
        </div>
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
                            ['08:00', '09:00'], ['09:00', '10:00'], ['10:00', '11:00'], ['11:00', '12:00'],
                            ['12:00', '13:00'], ['13:00', '14:00'], ['14:00', '15:00'], ['15:00', '16:00'],
                            ['16:00', '17:00'], ['17:00', '18:00'], ['18:00', '19:00'], ['19:00', '20:00']
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
                                    <strong><?= $ts[0] ?>-<?= $ts[1] ?></strong>
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
                                                <?= htmlspecialchars($slot['major_name'] ?? '') ?> -
                                                П<?= $slot['stream_number'] ?? '' ?>
                                                <?php if ($slot['slot_type'] === 'EXERCISE' && !empty($slot['group_number'])): ?>
                                                    Гр. <?= $slot['group_number'] ?>
                                                <?php endif; ?>
                                            </small>
                                            <br>
                                            <small class="text-muted">
                                                <i class="bi bi-door-open"></i> <?= htmlspecialchars($slot['room_number'] ?? '') ?>
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
