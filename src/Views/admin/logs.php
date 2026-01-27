<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-journal-text me-2"></i>Системни логове</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Няма записани логове.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Дата/Час</th>
                                <th>Код на събитие</th>
                                <th>Параметри</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= $log['id'] ?></td>
                                    <td><?= htmlspecialchars($log['timestamp']) ?></td>
                                    <td><code><?= htmlspecialchars($log['message_code']) ?></code></td>
                                    <td>
                                        <?php if (!empty($log['parameters'])): ?>
                                            <small><pre class="mb-0"><?= htmlspecialchars(json_encode(json_decode($log['parameters']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Страници">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">
                                        <i class="bi bi-chevron-left"></i> Предишна
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">
                                        Следваща <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
