<h2><i class="bi bi-sliders"></i> Академични настройки</h2>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="/admin/settings">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label for="academic_year" class="form-label">Учебна година</label>
                        <input type="number" class="form-control" id="academic_year" name="academic_year" 
                               value="<?= $settings->academic_year ?? date('Y') ?>" min="2020" max="2050" required>
                        <small class="text-muted">Началната година на учебната година (напр. 2024 за 2024/2025)</small>
                    </div>
                    
                    <h5 class="mt-4">Зимен семестър</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="winter_start" class="form-label">Начало</label>
                            <input type="date" class="form-control" id="winter_start" name="winter_semester_start" 
                                   value="<?= $settings->winter_semester_start ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="winter_end" class="form-label">Край</label>
                            <input type="date" class="form-control" id="winter_end" name="winter_semester_end" 
                                   value="<?= $settings->winter_semester_end ?? '' ?>">
                        </div>
                    </div>
                    
                    <h5 class="mt-4">Летен семестър</h5>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="summer_start" class="form-label">Начало</label>
                            <input type="date" class="form-control" id="summer_start" name="summer_semester_start" 
                                   value="<?= $settings->summer_semester_start ?? '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="summer_end" class="form-label">Край</label>
                            <input type="date" class="form-control" id="summer_end" name="summer_semester_end" 
                                   value="<?= $settings->summer_semester_end ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-fmi">
                            <i class="bi bi-check-lg"></i> Запази настройките
                        </button>
                        <a href="/admin" class="btn btn-secondary">Отказ</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-info-circle"></i> Информация
            </div>
            <div class="card-body">
                <p>Тези настройки определят периодите за генериране на разписания и изпитни сесии.</p>
                <ul>
                    <li>Зимен семестър: обикновено октомври - януари</li>
                    <li>Летен семестър: обикновено февруари - юни</li>
                </ul>
            </div>
        </div>
    </div>
</div>
