<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="bi bi-eye me-2"></i>
            Преглед на вариант: <?= htmlspecialchars($variant->name) ?>
        </h1>
        <a href="/admin/schedule" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Назад
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-header card-header-fmi">
            <i class="bi bi-info-circle"></i> Информация за варианта
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <strong>Тип:</strong>
                    <?php
                    $typeLabels = [
                        'WEEKLY' => 'Седмично разписание',
                        'TEST' => 'Контролни',
                        'EXAM' => 'Изпити'
                    ];
                    $typeKey = is_string($type) ? $type : '';
                    echo $typeLabels[$typeKey] ?? $typeKey;
                    ?>
                </div>
                <div class="col-md-3">
                    <strong>Семестър:</strong>
                    <?= $variant->academic_year ?>/<?= $variant->academic_year + 1 ?>,
                    <?= $variant->semester === 'WINTER' ? 'зимен' : 'летен' ?>
                </div>
                <div class="col-md-3">
                    <strong>Фитнес:</strong>
                    <?= number_format($variant->fitness_score, 4) ?>
                </div>
                <div class="col-md-3">
                    <strong>Статус:</strong>
                    <?php if ($variant->is_selected): ?>
                        <span class="badge bg-success">Избран</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Чакащ</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (empty($scheduleData)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Няма данни за това разписание.
        </div>
    <?php else: ?>

        <?php if ($type === 'WEEKLY'): ?>
            <!-- Filter Controls -->
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-funnel"></i> Филтри
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Специалности:</label>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($majors as $major): ?>
                                    <div class="form-check">
                                        <input class="form-check-input major-filter" type="checkbox" 
                                               value="<?= $major->id ?>" 
                                               id="major-<?= $major->id ?>" 
                                               data-abbr="<?= htmlspecialchars($major->abbreviation) ?>"
                                               checked>
                                        <label class="form-check-label" for="major-<?= $major->id ?>">
                                            <?= htmlspecialchars($major->name_bg) ?> (<?= htmlspecialchars($major->abbreviation) ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Курс (година):</label>
                            <div class="d-flex flex-wrap gap-3">
                                <div class="form-check">
                                    <input class="form-check-input year-filter" type="checkbox" value="1" id="year-1" checked>
                                    <label class="form-check-label" for="year-1">1-ви курс</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input year-filter" type="checkbox" value="2" id="year-2" checked>
                                    <label class="form-check-label" for="year-2">2-ри курс</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input year-filter" type="checkbox" value="3" id="year-3" checked>
                                    <label class="form-check-label" for="year-3">3-ти курс</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input year-filter" type="checkbox" value="4" id="year-4" checked>
                                    <label class="form-check-label" for="year-4">4-ти курс</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Курсове:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="elective" id="filter-elective" checked>
                                <label class="form-check-label" for="filter-elective">
                                    <i class="bi bi-star"></i> Избираеми курсове
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="select-all-filters">Избери всички</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="deselect-all-filters">Премахни всички</button>
                    </div>
                </div>
            </div>

            <!-- Weekly Schedule View - Timetable Grid -->
            <?php
            $days = ['MON' => 'Пон', 'TUE' => 'Вт', 'WED' => 'Ср', 'THU' => 'Чет', 'FRI' => 'Пет', 'SAT' => 'Съб', 'SUN' => 'Нед'];
            $daysFull = ['MON' => 'Понеделник', 'TUE' => 'Вторник', 'WED' => 'Сряда', 'THU' => 'Четвъртък', 'FRI' => 'Петък', 'SAT' => 'Събота', 'SUN' => 'Неделя'];
            
            // Fixed 1-hour time slots from 08:00 to 20:00
            $timeSlots = [
                '08:00', '09:00', '10:00', '11:00', '12:00', '13:00',
                '14:00', '15:00', '16:00', '17:00', '18:00', '19:00'
            ];
            
            // Organize slots by day and starting hour, tracking which cells to skip due to colspan
            $slotsByDayHour = [];
            $skipCells = []; // Track cells to skip due to previous slot colspan
            
            foreach ($scheduleData as $slot) {
                $startHour = substr($slot['start_time'], 0, 5);
                $slotsByDayHour[$slot['day_of_week']][$startHour][] = $slot;
            }
            ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="table-layout: fixed;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 80px;">Ден</th>
                                    <?php foreach ($timeSlots as $i => $startTime): ?>
                                        <?php 
                                        $endTime = isset($timeSlots[$i + 1]) ? $timeSlots[$i + 1] : '20:00';
                                        ?>
                                        <th class="text-center" style="min-width: 100px;">
                                            <?= $startTime ?>-<?= $endTime ?>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $dayCode => $dayName): ?>
                                    <?php 
                                    // Pre-calculate which cells to skip for this day due to colspan
                                    // A cell is skipped ONLY if:
                                    // 1. A previous slot spans into this time
                                    // 2. AND this cell has NO slots of its own starting here
                                    $cellsToSkip = [];
                                    foreach ($timeSlots as $idx => $time) {
                                        $slotsHere = $slotsByDayHour[$dayCode][$time] ?? [];
                                        if (!empty($slotsHere)) {
                                            $firstSlot = $slotsHere[0];
                                            $slotStart = strtotime($firstSlot['start_time']);
                                            $slotEnd = strtotime($firstSlot['end_time']);
                                            $durationHours = ($slotEnd - $slotStart) / 3600;
                                            $maxColspan = max(1, (int)$durationHours);
                                            
                                            // Only mark cells to skip if they have NO slots of their own
                                            for ($j = 1; $j < $maxColspan && isset($timeSlots[$idx + $j]); $j++) {
                                                $nextTime = $timeSlots[$idx + $j];
                                                $nextSlots = $slotsByDayHour[$dayCode][$nextTime] ?? [];
                                                if (empty($nextSlots)) {
                                                    $cellsToSkip[$nextTime] = true;
                                                } else {
                                                    break; // Stop if next cell has its own slots
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                    <tr>
                                        <td class="fw-bold align-middle text-center" title="<?= $daysFull[$dayCode] ?>">
                                            <?= $dayName ?>
                                        </td>
                                        <?php foreach ($timeSlots as $i => $startTime): ?>
                                            <?php 
                                            // Check if this cell should be skipped (covered by previous colspan)
                                            if (isset($cellsToSkip[$startTime])) {
                                                continue;
                                            }
                                            
                                            $slotsAtTime = $slotsByDayHour[$dayCode][$startTime] ?? [];
                                            
                                            // Calculate colspan: only extend into cells that have no slots of their own
                                            $colspan = 1;
                                            if (!empty($slotsAtTime)) {
                                                $firstSlot = $slotsAtTime[0];
                                                $slotStart = strtotime($firstSlot['start_time']);
                                                $slotEnd = strtotime($firstSlot['end_time']);
                                                $maxColspan = max(1, (int)(($slotEnd - $slotStart) / 3600));
                                                
                                                // Check how many consecutive empty cells we can span
                                                for ($j = 1; $j < $maxColspan && isset($timeSlots[$i + $j]); $j++) {
                                                    $nextTime = $timeSlots[$i + $j];
                                                    $nextSlots = $slotsByDayHour[$dayCode][$nextTime] ?? [];
                                                    if (!empty($nextSlots)) {
                                                        break; // Stop spanning if next cell has its own slots
                                                    }
                                                    $colspan++;
                                                }
                                            }
                                            ?>
                                            <td class="p-1" style="vertical-align: top; font-size: 0.85em;" <?= $colspan > 1 ? "colspan=\"$colspan\"" : '' ?>>
                                                <?php if (!empty($slotsAtTime)): ?>
                                                    <?php foreach ($slotsAtTime as $slot): ?>
                                                        <div class="schedule-slot mb-1 p-1 rounded <?= $slot['slot_type'] === 'LECTURE' ? 'bg-primary bg-opacity-25' : 'bg-info bg-opacity-25' ?>"
                                                             data-major-id="<?= $slot['course_major_id'] ?? $slot['stream_major_id'] ?? '' ?>"
                                                             data-is-elective="<?= $slot['is_elective'] ? '1' : '0' ?>"
                                                             data-course-year="<?= $slot['course_year'] ?? '' ?>">
                                                            <strong><?= htmlspecialchars($slot['course_code']) ?></strong>
                                                            <?php if ($slot['is_elective']): ?>
                                                                <i class="bi bi-star-fill text-warning" title="Избираем"></i>
                                                            <?php endif; ?>
                                                            <?php if (!empty($slot['course_year'])): ?>
                                                                <span class="badge bg-success" style="font-size: 0.65em;"><?= $slot['course_year'] ?> курс</span>
                                                            <?php endif; ?>
                                                            <br>
                                                            <small class="text-truncate d-block" title="<?= htmlspecialchars($slot['course_name']) ?>">
                                                                <?= htmlspecialchars(mb_substr($slot['course_name'], 0, 20)) ?><?= mb_strlen($slot['course_name']) > 20 ? '...' : '' ?>
                                                            </small>
                                                            <span class="badge <?= $slot['slot_type'] === 'LECTURE' ? 'bg-primary' : 'bg-info' ?>" style="font-size: 0.7em;">
                                                                <?= $slot['slot_type'] === 'LECTURE' ? 'Л' : 'У' ?>
                                                            </span>
                                                            <span class="badge bg-secondary" style="font-size: 0.7em;">
                                                                <?= htmlspecialchars($slot['room_number']) ?>
                                                            </span>
                                                            <?php if ($slot['major_abbr']): ?>
                                                                <span class="badge bg-dark" style="font-size: 0.7em;" title="<?= htmlspecialchars($slot['major_name'] ?? '') ?>">
                                                                    <?= htmlspecialchars($slot['major_abbr']) ?>
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if ($slot['group_name']): ?>
                                                                <br><small class="text-muted"><?= htmlspecialchars($slot['group_name']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
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
                <span class="badge bg-primary">Л</span> = Лекция &nbsp;
                <span class="badge bg-info">У</span> = Упражнение
            </div>

        <?php elseif ($type === 'TEST'): ?>
            <!-- Test Schedule View -->
            <div class="card">
                <div class="card-header card-header-fmi">
                    <i class="bi bi-pencil-square"></i> График на контролни
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Час</th>
                                <th>Курс</th>
                                <th>Контролно №</th>
                                <th>Зала</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduleData as $test): ?>
                                <tr>
                                    <td><?= htmlspecialchars($test['date']) ?></td>
                                    <td><?= substr($test['start_time'], 0, 5) ?> - <?= substr($test['end_time'], 0, 5) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($test['course_code']) ?></strong><br>
                                        <small><?= htmlspecialchars($test['course_name']) ?></small>
                                    </td>
                                    <td><?= $test['test_index'] ?></td>
                                    <td><?= htmlspecialchars($test['room_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($type === 'EXAM'): ?>
            <!-- Exam Schedule View -->
            <div class="card">
                <div class="card-header card-header-fmi">
                    <i class="bi bi-journal-check"></i> График на изпити
                    <?php if ($variant->session_type): ?>
                        (<?= $variant->session_type === 'REGULAR' ? 'Редовна сесия' : 'Ликвидационна сесия' ?>)
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Час</th>
                                <th>Курс</th>
                                <th>Изпит №</th>
                                <th>Зала</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($scheduleData as $exam): ?>
                                <tr>
                                    <td><?= htmlspecialchars($exam['date']) ?></td>
                                    <td><?= substr($exam['start_time'], 0, 5) ?> - <?= substr($exam['end_time'], 0, 5) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($exam['course_code']) ?></strong><br>
                                        <small><?= htmlspecialchars($exam['course_name']) ?></small>
                                    </td>
                                    <td><?= $exam['exam_index'] ?></td>
                                    <td><?= htmlspecialchars($exam['room_number']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    <?php endif; ?>

    <div class="mt-4">
        <?php if (!$variant->is_selected): ?>
            <form method="POST" action="/admin/schedule/select/<?= $variant->id ?>" class="d-inline">
                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                <button type="submit" class="btn btn-success">
                    <i class="bi bi-check-circle"></i> Избери този вариант
                </button>
            </form>
            <form method="POST" action="/admin/schedule/delete/<?= $variant->id ?>" class="d-inline ms-2">
                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                <button type="submit" class="btn btn-danger" onclick="return confirm('Сигурни ли сте?')">
                    <i class="bi bi-trash"></i> Изтрий
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if ($type === 'WEEKLY'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const majorFilters = document.querySelectorAll('.major-filter');
    const yearFilters = document.querySelectorAll('.year-filter');
    const electiveFilter = document.getElementById('filter-elective');
    const selectAllBtn = document.getElementById('select-all-filters');
    const deselectAllBtn = document.getElementById('deselect-all-filters');
    const scheduleSlots = document.querySelectorAll('.schedule-slot');
    
    function applyFilters() {
        // Get selected major IDs
        const selectedMajors = Array.from(majorFilters)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        // Get selected years
        const selectedYears = Array.from(yearFilters)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        const showElective = electiveFilter.checked;
        
        scheduleSlots.forEach(slot => {
            const majorId = slot.dataset.majorId;
            const isElective = slot.dataset.isElective === '1';
            const courseYear = slot.dataset.courseYear;
            
            let show = false;
            
            if (isElective) {
                // Elective courses: show if elective filter is checked
                show = showElective;
            } else {
                // Non-elective courses: show if their major is selected
                // If no major_id (e.g., general course), show if any major is selected
                let majorMatch = false;
                if (majorId === '' || majorId === null || majorId === undefined) {
                    majorMatch = selectedMajors.length > 0;
                } else {
                    majorMatch = selectedMajors.includes(majorId);
                }
                
                // Check year filter
                let yearMatch = false;
                if (courseYear === '' || courseYear === null || courseYear === undefined) {
                    // No year set - show if any year is selected
                    yearMatch = selectedYears.length > 0;
                } else {
                    yearMatch = selectedYears.includes(courseYear);
                }
                
                show = majorMatch && yearMatch;
            }
            
            slot.style.display = show ? '' : 'none';
        });
    }
    
    // Attach event listeners
    majorFilters.forEach(cb => cb.addEventListener('change', applyFilters));
    yearFilters.forEach(cb => cb.addEventListener('change', applyFilters));
    electiveFilter.addEventListener('change', applyFilters);
    
    selectAllBtn.addEventListener('click', function() {
        majorFilters.forEach(cb => cb.checked = true);
        yearFilters.forEach(cb => cb.checked = true);
        electiveFilter.checked = true;
        applyFilters();
    });
    
    deselectAllBtn.addEventListener('click', function() {
        majorFilters.forEach(cb => cb.checked = false);
        yearFilters.forEach(cb => cb.checked = false);
        electiveFilter.checked = false;
        applyFilters();
    });
    
    // Initial filter application (all shown by default)
    applyFilters();
});
</script>
<?php endif; ?>