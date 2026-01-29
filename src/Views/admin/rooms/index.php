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
    </div>
    
    <div class="table-responsive">
        <table class="table table-striped" id="roomsTable">
            <thead class="table-dark">
                <tr>
                    <th>Номер</th>
                    <th>Етаж</th>
                    <th>Бели дъски</th>
                    <th>Черни дъски</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($room->number ?? '') ?></code></td>
                        <td><?= $room->floor ?? '-' ?></td>
                        <td><?= $room->white_boards ?? 0 ?></td>
                        <td><?= $room->black_boards ?? 0 ?></td>
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

function filterRooms() {
    const search = document.getElementById('searchRoom').value.toLowerCase();
    
    document.querySelectorAll('#roomsTable tbody tr').forEach(row => {
        const roomNumber = row.querySelector('code').textContent.toLowerCase();
        
        // Filter by prefix (startsWith)
        const matchesSearch = roomNumber.startsWith(search);
        
        row.style.display = matchesSearch ? '' : 'none';
    });
}
</script>
