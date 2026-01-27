<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow">
            <div class="card-header card-header-fmi text-center">
                <h4 class="mb-0">
                    <i class="bi bi-box-arrow-in-right"></i> Вход в системата
                </h4>
            </div>
            <div class="card-body">
                <form method="POST" action="/login">
                    <input type="hidden" name="_csrf" value="<?= $this->session->getCsrfToken() ?>">
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Имейл адрес</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               required autofocus placeholder="email@fmi.uni-sofia.bg">
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Парола</label>
                        <input type="password" class="form-control" id="password" name="password" 
                               required placeholder="••••••••">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-fmi">
                            <i class="bi bi-box-arrow-in-right"></i> Вход
                        </button>
                    </div>
                </form>
                
                <hr>
                
                <div class="text-center">
                    <a href="/forgot-password" class="text-muted">Забравена парола?</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3">
            <a href="/" class="text-muted">
                <i class="bi bi-arrow-left"></i> Обратно към началната страница
            </a>
        </div>
    </div>
</div>
