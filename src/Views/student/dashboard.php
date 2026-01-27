<h2><i class="bi bi-calendar-week"></i> Моето разписание</h2>

<p class="text-muted">
    Добре дошли, <?= htmlspecialchars($student['first_name'] ?? '') ?>!
    <br>
    <?= htmlspecialchars($student['major_name'] ?? '') ?> - <?= htmlspecialchars($student['stream_name'] ?? '') ?>,
    Група <?= $student['group_number'] ?? '' ?>
</p>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-calendar-week"></i> Седмично разписание
            </div>
            <div class="card-body">
                <?php if (empty($schedule)): ?>
                    <p class="text-muted">Няма налично разписание.</p>
                <?php else: ?>
                    <?php
                    $days = ['MON' => 'Пон', 'TUE' => 'Вт', 'WED' => 'Ср', 'THU' => 'Чет', 'FRI' => 'Пет'];
                    $byDay = [];
                    foreach ($schedule as $slot) {
                        $byDay[$slot['day_of_week']][] = $slot;
                    }
                    ?>
                    <div class="row">
                        <?php foreach ($days as $code => $name): ?>
                            <div class="col">
                                <h6 class="text-center"><?= $name ?></h6>
                                <?php foreach ($byDay[$code] ?? [] as $slot): ?>
                                    <div class="schedule-slot slot-<?= strtolower($slot['slot_type'] ?? 'lecture') ?> mb-2">
                                        <small><?= substr($slot['start_time'], 0, 5) ?></small>
                                        <br>
                                        <strong><?= htmlspecialchars($slot['course_name'] ?? '') ?></strong>
                                        <br>
                                        <small><?= htmlspecialchars($slot['room_number'] ?? '') ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <a href="/student/schedule" class="btn btn-fmi mt-2">
                    <i class="bi bi-eye"></i> Пълно разписание
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-bookmark-star"></i> Избираеми дисциплини
            </div>
            <div class="card-body">
                <?php if (empty($electives)): ?>
                    <p class="text-muted">Не сте записани за избираеми дисциплини.</p>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach ($electives as $e): ?>
                            <li class="mb-2">
                                <i class="bi bi-check-circle text-success"></i>
                                <?= htmlspecialchars($e['name_bg']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="/student/electives" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-plus-circle"></i> Управление
                </a>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-pencil-square"></i> Предстоящи контролни
            </div>
            <div class="card-body">
                <?php 
                $upcomingTests = array_filter($tests ?? [], function($t) {
                    return $t['test_date'] >= date('Y-m-d');
                });
                $upcomingTests = array_slice($upcomingTests, 0, 3);
                ?>
                <?php if (empty($upcomingTests)): ?>
                    <p class="text-muted">Няма предстоящи контролни.</p>
                <?php else: ?>
                    <ul class="list-unstyled">
                        <?php foreach ($upcomingTests as $t): ?>
                            <li class="mb-2">
                                <strong><?= date('d.m', strtotime($t['test_date'])) ?></strong>
                                - <?= htmlspecialchars($t['course_name'] ?? '') ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <a href="/schedule/tests?stream_id=<?= $student['stream_id'] ?? '' ?>" class="btn btn-outline-secondary btn-sm">
                    Виж всички
                </a>
            </div>
        </div>
    </div>
</div>
