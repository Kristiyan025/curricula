# Curricula - Система за управление на разписания (Версия на проекта - финална)

Уеб приложение за генериране и управление на учебни разписания за Факултета по математика и информатика, Софийски университет "Св. Климент Охридски".

## Функционалности

### За администратори

- Управление на специалности, потоци и групи
- Управление на потребители (преподаватели, асистенти, студенти)
- Управление на зали и оборудване
- Управление на дисциплини и учебни планове
- Генериране на разписания (седмични, контролни, изпити)
- Избор между множество варианти на разписание
- Настройки на учебната година и семестрите

### За преподаватели

- Преглед на възложените дисциплини
- Задаване на предпочитания за време
- Дефиниране на периоди за провеждане на контролни

### За студенти

- Преглед на седмичното разписание
- Записване за избираеми дисциплини
- Преглед на разписание на контролни и изпити

### Публичен достъп

- Преглед на разписания по поток, група, зала или преподавател

## Технологии

- **Backend**: PHP 7.4+
- **База данни**: MySQL 8.0+
- **Frontend**: Bootstrap 5.3, Bootstrap Icons
- **Архитектура**: MVC pattern
- **Алгоритъм**: Hybrid GA/CSP (Genetic Algorithm + Constraint Satisfaction)

## Изисквания

- PHP 7.4 или по-нова версия
- MySQL 8.0 или по-нова версия
- Composer
- Apache/Nginx уеб сървър с mod_rewrite

## Инсталация

### 1. Клониране на проекта

```bash
git clone <repository-url> curricula
cd curricula
```

### 2. Инсталиране на зависимости

```bash
composer install
```

### 3. Конфигурация

Копирайте примерния конфигурационен файл и го редактирайте:

```bash
cp config/config.example.php config/config.php
```

Редактирайте `config/config.php` с вашите настройки:

```php
return [
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'curricula',
        'user' => 'your_username',
        'pass' => 'your_password',
        'charset' => 'utf8mb4'
    ],
    'app' => [
        'name' => 'Curricula',
        'debug' => false,
        'url' => 'http://localhost/curricula'
    ]
];
```

### 4. Създаване на базата данни

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p curricula < database/seed.sql
```

Или използвайте MySQL клиент:

```sql
CREATE DATABASE curricula CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE curricula;
SOURCE database/schema.sql;
SOURCE database/seed.sql;
```

### 5. Конфигуриране на уеб сървъра

#### Apache

Уверете се, че `mod_rewrite` е активиран и `AllowOverride All` е зададен за директорията.

Примерен VirtualHost:

```apache
<VirtualHost *:80>
    ServerName curricula.local
    DocumentRoot /path/to/curricula/public

    <Directory /path/to/curricula/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

#### Nginx

```nginx
server {
    listen 80;
    server_name curricula.local;
    root /path/to/curricula/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 6. Права за достъп

```bash
chmod -R 755 storage/
chmod -R 755 logs/
```

## Първоначален достъп

След инсталацията, използвайте следните данни за вход:

- **Администратор**: admin@fmi.uni-sofia.bg / admin123
- **Преподавател**: lecturer@fmi.uni-sofia.bg / lecturer123
- **Студент**: student@fmi.uni-sofia.bg / student123

**Важно**: Променете паролите след първото влизане!

## Структура на проекта

```
curricula/
├── config/              # Конфигурационни файлове
│   └── config.php
├── database/            # SQL скриптове
│   ├── schema.sql       # Структура на базата
│   └── seed.sql         # Примерни данни
├── logs/                # Log файлове
├── public/              # Публична директория (document root)
│   ├── index.php        # Entry point
│   ├── css/             # Стилове
│   ├── js/              # JavaScript
│   └── .htaccess        # Apache правила
├── src/                 # Изходен код
│   ├── Controllers/     # Контролери
│   │   ├── Api/         # API контролери
│   │   └── ...
│   ├── Core/            # Ядро на приложението
│   ├── Database/        # Database класове
│   ├── Models/          # Модели (Entities)
│   ├── Services/        # Бизнес логика
│   │   └── Scheduling/  # Генератори на разписания
│   ├── Utils/           # Помощни класове
│   └── Views/           # Изгледи (templates)
│       ├── admin/       # Админ панел
│       ├── auth/        # Автентикация
│       ├── home/        # Начална страница
│       ├── layouts/     # Layouts
│       ├── lecturer/    # Преподавателски панел
│       ├── schedule/    # Разписания
│       └── student/     # Студентски панел
├── storage/             # Съхранение на файлове
├── composer.json        # Composer зависимости
└── README.md            # Документация
```

## API Endpoints

### Автентикация

```
POST /api/auth/login     - Вход
POST /api/auth/logout    - Изход
GET  /api/auth/me        - Текущ потребител
```

### Разписания

```
GET  /api/schedule/stream/{id}           - Разписание на поток
GET  /api/schedule/group/{id}            - Разписание на група
GET  /api/schedule/tests/{stream_id}     - Контролни за поток
GET  /api/schedule/exams                 - Изпитна сесия
POST /api/schedule/generate              - Генериране (admin)
```

### Дисциплини

```
GET  /api/courses                        - Списък дисциплини
GET  /api/courses/{id}                   - Детайли за дисциплина
GET  /api/courses/electives              - Избираеми дисциплини
POST /api/courses/enroll                 - Записване
```

### Потребители

```
GET  /api/users                          - Списък (admin)
GET  /api/users/{id}                     - Детайли
GET  /api/users/lecturers                - Преподаватели
GET  /api/users/students                 - Студенти
```

## Генериране на разписания

Системата използва хибриден алгоритъм, комбиниращ Генетичен алгоритъм (GA) с Constraint Satisfaction Problem (CSP).

### Параметри на алгоритъма

```php
'population_size' => 50,    // Размер на популацията
'generations' => 500,       // Брой поколения
'mutation_rate' => 0.1,     // Честота на мутация
'tournament_size' => 5,     // Размер на турнира
'max_time' => 1200,         // Макс. време в секунди (20 мин)
```

### Видове разписания

1. **Седмично разписание** - Лекции и упражнения (Пон-Пет, 08:00-18:00)
2. **Контролни** - В рамките на зададени от преподавателите периоди
3. **Изпитна сесия** - Редовна и ликвидационна

### Ограничения (constraints)

- Без застъпване на зали
- Без застъпване на преподаватели
- Без застъпване на групи/потоци
- Спазване на капацитет на залите
- Съобразяване с изискванията за оборудване
- Мин. 2 дни между изпити на един поток
- Предшестващи дисциплини преди зависими

## Локализация

Интерфейсът е на български език. API полетата използват английски ключове за по-лесна интеграция.

## Сигурност

- CSRF защита за всички форми
- Password hashing с bcrypt
- Prepared statements за всички SQL заявки
- Session-based автентикация
- Role-based access control

## Лицензия

Този проект е разработен за учебни цели.

## Автори

Разработено за курса "Уеб технологии" - ФМИ, СУ "Св. Климент Охридски"

---

## Известни проблеми

- Генерирането на разписание може да отнеме значително време за големи данни
- При много конфликти, алгоритъмът може да не намери оптимално решение

## Поддръжка

За въпроси и проблеми, моля отворете issue в GitHub repository.
