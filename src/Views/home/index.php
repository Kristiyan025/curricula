<div class="row">
    <div class="col-md-8">
        <h1>Добре дошли в Curricula</h1>
        <p class="lead">
            Система за управление на разписанията във Факултета по Математика и Информатика, 
            Софийски университет "Св. Климент Охридски"
        </p>
        
        <div class="row mt-4">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-fmi">
                        <i class="bi bi-calendar-week"></i> Седмични разписания
                    </div>
                    <div class="card-body">
                        <p>Преглед на седмичните разписания за лекции и упражнения по специалности, групи или преподаватели.</p>
                        <a href="/schedule/stream" class="btn btn-fmi">Виж разписания</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-fmi">
                        <i class="bi bi-pencil-square"></i> Контролни
                    </div>
                    <div class="card-body">
                        <p>График на контролните работи за текущия семестър.</p>
                        <a href="/schedule/tests" class="btn btn-fmi">Виж график</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-fmi">
                        <i class="bi bi-journal-check"></i> Изпитна сесия
                    </div>
                    <div class="card-body">
                        <p>Разписание на изпитите за редовна и ликвидационна сесия.</p>
                        <a href="/schedule/exams" class="btn btn-fmi">Виж изпити</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header card-header-fmi">
                        <i class="bi bi-door-open"></i> Зали
                    </div>
                    <div class="card-body">
                        <p>Преглед на заетостта на залите в учебните корпуси.</p>
                        <a href="/schedule/room" class="btn btn-fmi">Виж зали</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-mortarboard"></i> Специалности
            </div>
            <div class="card-body">
                <div class="accordion" id="majorsAccordion">
                    <?php foreach ($majors ?? [] as $index => $major): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#major<?= $major['id'] ?>">
                                    <?= htmlspecialchars($major['name_bg']) ?>
                                </button>
                            </h2>
                            <div id="major<?= $major['id'] ?>" class="accordion-collapse collapse" 
                                 data-bs-parent="#majorsAccordion">
                                <div class="accordion-body">
                                    <?php 
                                    $streams = \App\Models\MajorStream::where('major_id', $major['id']);
                                    foreach ($streams as $stream): 
                                    ?>
                                        <a href="/schedule/stream?stream_id=<?= $stream['id'] ?>" 
                                           class="d-block mb-1">
                                            <?= htmlspecialchars($stream['name'] ?? $stream['name_bg'] ?? '') ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <?php if (isset($period)): ?>
        <div class="card mt-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-info-circle"></i> Текуща учебна година
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Учебна година:</strong> 
                    <?= $period['year'] ?>/<?= $period['year'] + 1 ?>
                </p>
                <p class="mb-0">
                    <strong>Семестър:</strong> 
                    <?= $period['semester'] === 'WINTER' ? 'Зимен' : 'Летен' ?>
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
