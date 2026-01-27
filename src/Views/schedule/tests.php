<h2><i class="bi bi-pencil-square"></i> Разписание на контролни</h2>

<?php if ($period ?? null): ?>
    <p class="text-muted">
        Учебна година <?= $period['year'] ?>/<?= $period['year'] + 1 ?>,
        <?= $period['semester'] === 'WINTER' ? 'зимен' : 'летен' ?> семестър
    </p>
<?php endif; ?>

<div class="row mb-4">
    <div class="col-md-4">
        <form method="GET" action="/schedule/tests">
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
        </form>
    </div>
</div>

<?php if (empty($schedule)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Няма насрочени контролни работи за този период.
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
                usort($schedule, fn($a, $b) => strcmp($a['test_date'] . $a['start_time'], $b['test_date'] . $b['start_time']));
                foreach ($schedule as $test): 
                    $testDate = new DateTime($test['test_date']);
                ?>
                    <tr>
                        <td>
                            <strong><?= $testDate->format('d.m.Y') ?></strong>
                            <br>
                            <small class="text-muted">
                                <?php
                                $dayNames = ['Нед', 'Пон', 'Вт', 'Ср', 'Чет', 'Пет', 'Съб'];
                                echo $dayNames[(int)$testDate->format('w')];
                                ?>
                            </small>
                        </td>
                        <td><?= substr($test['start_time'], 0, 5) ?> - <?= substr($test['end_time'], 0, 5) ?></td>
                        <td>
                            <strong><?= htmlspecialchars($test['course_name'] ?? $test['course_code'] ?? 'N/A') ?></strong>
                        </td>
                        <td><?= htmlspecialchars($test['stream_name'] ?? '-') ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?= htmlspecialchars($test['room_number'] ?? 'TBD') ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
