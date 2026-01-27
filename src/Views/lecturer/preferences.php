<h2><i class="bi bi-sliders"></i> Моите предпочитания</h2>

<a href="/lecturer" class="btn btn-secondary mb-3">
    <i class="bi bi-arrow-left"></i> Обратно
</a>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-clock"></i> Предпочитания за време
            </div>
            <div class="card-body">
                <form method="POST" action="/lecturer/preferences">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    <input type="hidden" name="type" value="time">
                    
                    <p class="text-muted">
                        Изберете предпочитаните дни и часове за провеждане на занятия.
                        Системата ще се опита да спази предпочитанията ви при генериране на разписанието.
                    </p>
                    
                    <table class="table table-bordered">
                        <thead class="table-secondary">
                            <tr>
                                <th></th>
                                <th class="text-center">Пон</th>
                                <th class="text-center">Вт</th>
                                <th class="text-center">Ср</th>
                                <th class="text-center">Чет</th>
                                <th class="text-center">Пет</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $timeSlots = [
                                '08:00' => '08:00 - 10:00',
                                '10:00' => '10:00 - 12:00',
                                '12:00' => '12:00 - 14:00',
                                '14:00' => '14:00 - 16:00',
                                '16:00' => '16:00 - 18:00',
                                '18:00' => '18:00 - 20:00',
                            ];
                            $days = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
                            ?>
                            <?php foreach ($timeSlots as $time => $label): ?>
                                <tr>
                                    <td><?= $label ?></td>
                                    <?php foreach ($days as $day): ?>
                                        <?php
                                        $key = $day . '_' . $time;
                                        $pref = $preferences[$key] ?? 'NEUTRAL';
                                        ?>
                                        <td class="text-center">
                                            <select class="form-select form-select-sm" name="prefs[<?= $key ?>]">
                                                <option value="PREFERRED" <?= $pref === 'PREFERRED' ? 'selected' : '' ?>>✓ Предпочитам</option>
                                                <option value="NEUTRAL" <?= $pref === 'NEUTRAL' ? 'selected' : '' ?>>○ Неутрално</option>
                                                <option value="AVOID" <?= $pref === 'AVOID' ? 'selected' : '' ?>>✗ Избягвам</option>
                                            </select>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <button type="submit" class="btn btn-fmi">
                        <i class="bi bi-save"></i> Запази предпочитания
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-person-badge"></i> Профил
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm">
                    <tr>
                        <th>Титла:</th>
                        <td><?= htmlspecialchars($lecturer['title'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Име:</th>
                        <td><?= htmlspecialchars($lecturer['first_name'] . ' ' . $lecturer['last_name']) ?></td>
                    </tr>
                    <tr>
                        <th>Катедра:</th>
                        <td><?= htmlspecialchars($lecturer['department'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>Макс. часове/седм.:</th>
                        <td><?= $lecturer['max_hours_per_week'] ?? 20 ?></td>
                    </tr>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-body">
                <h6><i class="bi bi-info-circle"></i> Информация</h6>
                <p class="small text-muted">
                    Предпочитанията се използват като подсказки при генериране на разписание.
                    Системата се опитва да ги спази, но не гарантира 100% съответствие поради
                    други ограничения (налични зали, конфликти и др.).
                </p>
            </div>
        </div>
    </div>
</div>
