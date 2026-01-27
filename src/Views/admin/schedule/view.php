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
                    echo $typeLabels[$type] ?? (string)$type;
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
                        <div class="col-md-8">
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
            
            // Collect all unique hours and organize slots
            $hours = [];
            $slotsByDayHour = [];
            foreach ($scheduleData as $slot) {
                $startHour = substr($slot['start_time'], 0, 5);
                $endHour = substr($slot['end_time'], 0, 5);
                $timeKey = $startHour . '-' . $endHour;
                $hours[$timeKey] = ['start' => $startHour, 'end' => $endHour];
                $slotsByDayHour[$slot['day_of_week']][$timeKey][] = $slot;
            }
            
            // Sort hours by start time
            uksort($hours, function($a, $b) use ($hours) {
                return strcmp($hours[$a]['start'], $hours[$b]['start']);
            });
            ?>
            
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm mb-0" style="table-layout: fixed;">
                            <thead class="table-dark">
                                <tr>
                                    <th style="width: 80px;">Ден</th>
                                    <?php foreach ($hours as $timeKey => $time): ?>
                                        <th class="text-center" style="min-width: 120px;">
                                            <?= $time['start'] ?><br>
                                            <small class="text-muted"><?= $time['end'] ?></small>
                                        </th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $dayCode => $dayName): ?>
                                    <tr>
                                        <td class="fw-bold align-middle text-center" title="<?= $daysFull[$dayCode] ?>">
                                            <?= $dayName ?>
                                        </td>
                                        <?php foreach ($hours as $timeKey => $time): ?>
                                            <td class="p-1" style="vertical-align: top; font-size: 0.85em;">
                                                <?php if (!empty($slotsByDayHour[$dayCode][$timeKey])): ?>
                                                    <?php foreach ($slotsByDayHour[$dayCode][$timeKey] as $slot): ?>
                                                        <div class="schedule-slot mb-1 p-1 rounded <?= $slot['slot_type'] === 'LECTURE' ? 'bg-primary bg-opacity-25' : 'bg-info bg-opacity-25' ?>"
                                                             data-major-id="<?= $slot['course_major_id'] ?? $slot['stream_major_id'] ?? '' ?>"
                                                             data-is-elective="<?= $slot['is_elective'] ? '1' : '0' ?>">
                                                            <strong><?= htmlspecialchars($slot['course_code']) ?></strong>
                                                            <?php if ($slot['is_elective']): ?>
                                                                <i class="bi bi-star-fill text-warning" title="Избираем"></i>
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
    const electiveFilter = document.getElementById('filter-elective');
    const selectAllBtn = document.getElementById('select-all-filters');
    const deselectAllBtn = document.getElementById('deselect-all-filters');
    const scheduleSlots = document.querySelectorAll('.schedule-slot');
    
    function applyFilters() {
        // Get selected major IDs
        const selectedMajors = Array.from(majorFilters)
            .filter(cb => cb.checked)
            .map(cb => cb.value);
        
        const showElective = electiveFilter.checked;
        
        scheduleSlots.forEach(slot => {
            const majorId = slot.dataset.majorId;
            const isElective = slot.dataset.isElective === '1';
            
            let show = false;
            
            if (isElective) {
                // Elective courses: show if elective filter is checked
                show = showElective;
            } else {
                // Non-elective courses: show if their major is selected
                // If no major_id (e.g., general course), show if any major is selected
                if (majorId === '' || majorId === null) {
                    show = selectedMajors.length > 0;
                } else {
                    show = selectedMajors.includes(majorId);
                }
            }
            
            slot.style.display = show ? '' : 'none';
        });
    }
    
    // Attach event listeners
    majorFilters.forEach(cb => cb.addEventListener('change', applyFilters));
    electiveFilter.addEventListener('change', applyFilters);
    
    selectAllBtn.addEventListener('click', function() {
        majorFilters.forEach(cb => cb.checked = true);
        electiveFilter.checked = true;
        applyFilters();
    });
    
    deselectAllBtn.addEventListener('click', function() {
        majorFilters.forEach(cb => cb.checked = false);
        electiveFilter.checked = false;
        applyFilters();
    });
    
    // Initial filter application (all shown by default)
    applyFilters();
});
</script>
<?php endif; ?>