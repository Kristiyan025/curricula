<h2><i class="bi bi-door-open"></i> Зали</h2>

<div class="mb-3">
    <a href="/admin/rooms/create" class="btn btn-fmi">
        <i class="bi bi-plus-circle"></i> Нова зала
    </a>
    <a href="/admin" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i> Обратно
    </a>
</div>

<?php if (empty($rooms)): ?>
    <div class="alert alert-info">
        Няма създадени зали.
    </div>
<?php else: ?>
    <div class="row mb-3">
        <div class="col-md-4">
            <input type="text" class="form-control" id="searchRoom" placeholder="Търсене по номер...">
        </div>
        <div class="col-md-4">
            <select class="form-select" id="filterType">
                <option value="">Всички типове</option>
                <option value="LECTURE_HALL">Лекционна зала</option>
                <option value="COMPUTER_LAB">Компютърна зала</option>
                <option value="SEMINAR_ROOM">Семинарна зала</option>
            </select>
        </div>
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped" id="roomsTable">
            <thead class="table-dark">
                <tr>
                    <th>Номер</th>
                    <th>Тип</th>
                    <th>Сграда</th>
                    <th>Етаж</th>
                    <th>Капацитет</th>
                    <th>Оборудване</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr data-type="<?= $room->room_type ?>">
                        <td><code><?= htmlspecialchars($room->room_number) ?></code></td>
                        <td>
                            <?php
                            $types = [
                                'LECTURE_HALL' => ['Лекционна', 'primary'],
                                'COMPUTER_LAB' => ['Компютърна', 'success'],
                                'SEMINAR_ROOM' => ['Семинарна', 'info']
                            ];
                            $type = $types[$room->room_type] ?? ['Друга', 'secondary'];
                            ?>
                            <span class="badge bg-<?= $type[1] ?>"><?= $type[0] ?></span>
                        </td>
                        <td><?= htmlspecialchars($room->building ?? '-') ?></td>
                        <td><?= $room->floor ?? '-' ?></td>
                        <td><?= $room->capacity ?> места</td>
                        <td>
                            <?php if ($room->has_projector): ?>
                                <i class="bi bi-tv text-success" title="Проектор"></i>
                            <?php endif; ?>
                            <?php if ($room->has_computers): ?>
                                <i class="bi bi-pc-display text-info" title="Компютри"></i>
                            <?php endif; ?>
                            <?php if ($room->has_whiteboard): ?>
                                <i class="bi bi-easel text-secondary" title="Бяла дъска"></i>
                            <?php endif; ?>
                            <?php if ($room->is_accessible): ?>
                                <i class="bi bi-universal-access text-primary" title="Достъпна"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/rooms/<?= $room->id ?>/edit" class="btn btn-sm btn-warning">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="/admin/rooms/<?= $room->id ?>/delete" class="d-inline"
                                  onsubmit="return confirm('Сигурни ли сте?')">
                                <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<script>
document.getElementById('searchRoom')?.addEventListener('input', filterRooms);
document.getElementById('filterType')?.addEventListener('change', filterRooms);

function filterRooms() {
    const search = document.getElementById('searchRoom').value.toLowerCase();
    const type = document.getElementById('filterType').value;
    
    document.querySelectorAll('#roomsTable tbody tr').forEach(row => {
        const roomNumber = row.querySelector('code').textContent.toLowerCase();
        const roomType = row.dataset.type;
        
        const matchesSearch = roomNumber.includes(search);
        const matchesType = !type || roomType === type;
        
        row.style.display = matchesSearch && matchesType ? '' : 'none';
    });
}
</script>
