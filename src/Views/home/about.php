<h2><i class="bi bi-info-circle"></i> За системата</h2>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <h4>Curricula - Система за управление на разписания</h4>
                <p class="lead">
                    Уеб приложение за генериране и управление на учебни разписания за 
                    Факултета по математика и информатика, Софийски университет "Св. Климент Охридски".
                </p>
                
                <hr>
                
                <h5>Основни функционалности</h5>
                <ul>
                    <li>Автоматично генериране на седмични разписания</li>
                    <li>Планиране на контролни работи</li>
                    <li>Генериране на изпитни сесии</li>
                    <li>Записване за избираеми дисциплини</li>
                    <li>Преглед на разписания по поток, група, преподавател или зала</li>
                </ul>
                
                <h5>Алгоритъм за генериране</h5>
                <p>
                    Системата използва хибриден алгоритъм, комбиниращ 
                    <strong>Генетичен алгоритъм (GA)</strong> с 
                    <strong>Constraint Satisfaction Problem (CSP)</strong> техники
                    за намиране на оптимално разписание.
                </p>
                
                <h5>Ограничения</h5>
                <ul>
                    <li>Без застъпване на зали в един и същи момент</li>
                    <li>Без застъпване на преподаватели</li>
                    <li>Без застъпване на групи/потоци</li>
                    <li>Спазване на капацитет на залите</li>
                    <li>Съобразяване с изискванията за оборудване</li>
                    <li>Задължителни дисциплини: 08:00 - 18:00</li>
                    <li>Избираеми дисциплини: до 22:00</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header card-header-fmi">
                <i class="bi bi-building"></i> Контакти
            </div>
            <div class="card-body">
                <p>
                    <strong>Факултет по математика и информатика</strong><br>
                    Софийски университет "Св. Климент Охридски"
                </p>
                <p>
                    <i class="bi bi-geo-alt"></i> бул. "Джеймс Баучер" 5<br>
                    1164 София, България
                </p>
                <p>
                    <i class="bi bi-globe"></i> 
                    <a href="https://www.fmi.uni-sofia.bg" target="_blank">www.fmi.uni-sofia.bg</a>
                </p>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header card-header-fmi">
                <i class="bi bi-code-square"></i> Технологии
            </div>
            <div class="card-body">
                <ul class="list-unstyled mb-0">
                    <li><i class="bi bi-check"></i> PHP 7.4+</li>
                    <li><i class="bi bi-check"></i> MySQL 8.0+</li>
                    <li><i class="bi bi-check"></i> Bootstrap 5</li>
                    <li><i class="bi bi-check"></i> MVC Architecture</li>
                </ul>
            </div>
        </div>
    </div>
</div>
