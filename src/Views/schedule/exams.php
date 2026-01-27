<h2><i class="bi bi-journal-check"></i> Изпитна сесия</h2>

<?php if ($period ?? null): ?>
    <p class="text-muted">
        Учебна година <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
        <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
    </p>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" action="/schedule/exams">
            <label for="streamSelect" class="form-label">Филтър по поток:</label>
            <select class="form-select" id="streamSelect" name="stream_id" onchange="this.form.submit()">
                <option value="">Всички потоци</option>
                <?php foreach ($majors ?? [] as $m): ?>
                    <?php $mStreams = \App\Models\MajorStream::where('major_id', $m['id']); ?>
                    <optgroup label="<?= htmlspecialchars($m['name_bg']) ?>">
                        <?php foreach ($mStreams as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= ($stream ?? null) && $stream['id'] == $s['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <input type="hidden" name="session" value="<?= htmlspecialchars($sessionType ?? 'REGULAR') ?>">
        </form>
    </div>
    <div class="col-md-4">
        <label class="form-label">Тип сесия:</label>
        <div class="btn-group d-block">
            <a href="?stream_id=<?= ($stream ?? null) ? $stream['id'] : '' ?>&session=REGULAR" 
               class="btn <?= ($sessionType ?? 'REGULAR') === 'REGULAR' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                Редовна
            </a>
            <a href="?stream_id=<?= ($stream ?? null) ? $stream['id'] : '' ?>&session=LIQUIDATION" 
               class="btn <?= ($sessionType ?? '') === 'LIQUIDATION' ? 'btn-fmi' : 'btn-outline-secondary' ?>">
                Ликвидационна
            </a>
        </div>
    </div>
</div>

<?php if (empty($schedule)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Няма насрочени изпити за този период.
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Дата</th>
                    <th>Час</th>
                    <th>Дисциплина</th>
                    <th>Поток</th>
                    <th>Зала</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                usort($schedule, fn($a, $b) => strcmp($a['exam_date'] . $a['start_time'], $b['exam_date'] . $b['start_time']));
                foreach ($schedule as $exam): 
                    $examDate = new DateTime($exam['exam_date']);
                ?>
                    <tr>
                        <td>
                            <strong><?= $examDate->format('d.m.Y') ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php
                                $dayNames = ['Нед', 'Пон', 'Вт', 'Ср', 'Чет', 'Пет', 'Съб'];
                                echo $dayNames[(int)$examDate->format('w')];
                                ?>
                            </small>
                        </td>
                        <td><?= substr($exam['start_time'], 0, 5) ?> - <?= substr($exam['end_time'], 0, 5) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($exam['course_name'] ?? $exam['course_code'] ?? 'N/A') ?></strong>
                        </td>
                        <td><?= htmlspecialchars($exam['stream_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($exam['room_number'] ?? 'TBD') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
