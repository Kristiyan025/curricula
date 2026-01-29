-- =====================================================
-- Curricula Database Sample Data
-- Sample data for testing and development
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- MAJORS, STREAMS, AND GROUPS
-- =====================================================

-- Majors (Специалности)
INSERT INTO `major` (`id`, `name_bg`, `abbreviation`) VALUES
(1, 'Информатика', 'INF'),
(2, 'Компютърни науки', 'CS'),
(3, 'Софтуерно инженерство', 'SE'),
(4, 'Математика', 'MATH'),
(5, 'Приложна математика', 'APMATH');

-- Major Streams (Потоци)
INSERT INTO `major_stream` (`id`, `major_id`, `name_bg`) VALUES
(1, 1, 'Поток 1'),
(2, 1, 'Поток 2'),
(3, 2, 'Поток 1'),
(4, 3, 'Поток 1'),
(5, 4, 'Поток 1'),
(6, 5, 'Поток 1');

-- Groups (Групи)
INSERT INTO `student_group` (`id`, `stream_id`, `name_bg`) VALUES
-- Информатика - Поток 1 (3 групи)
(1, 1, 'Група 1'),
(2, 1, 'Група 2'),
(3, 1, 'Група 3'),
-- Информатика - Поток 2 (3 групи)
(4, 2, 'Група 1'),
(5, 2, 'Група 2'),
(6, 2, 'Група 3'),
-- КН - Поток 1 (4 групи)
(7, 3, 'Група 1'),
(8, 3, 'Група 2'),
(9, 3, 'Група 3'),
(10, 3, 'Група 4'),
-- СИ - Поток 1 (3 групи)
(11, 4, 'Група 1'),
(12, 4, 'Група 2'),
(13, 4, 'Група 3'),
-- Математика (2 групи)
(14, 5, 'Група 1'),
(15, 5, 'Група 2'),
-- Приложна математика (2 групи)
(16, 6, 'Група 1'),
(17, 6, 'Група 2');

-- =====================================================
-- ROOMS (Аудитории)
-- =====================================================

INSERT INTO `room` (`number`, `floor`, `white_boards`, `black_boards`) VALUES
-- Floor 1
('101', 1, 2, 1),
('102', 1, 1, 2),
('103', 1, 1, 1),
('104', 1, 2, 0),
('105', 1, 1, 1),
-- Floor 2
('201', 2, 2, 1),
('202', 2, 1, 2),
('203', 2, 3, 0),
('204', 2, 1, 1),
('205', 2, 2, 1),
-- Floor 3
('301', 3, 2, 1),
('302', 3, 1, 1),
('303', 3, 2, 2),
('304', 3, 1, 0),
('305', 3, 1, 1),
-- Floor 4
('401', 4, 2, 1),
('402', 4, 1, 1),
('403', 4, 3, 1),
('404', 4, 2, 0),
('405', 4, 1, 1),
-- Floor 5
('501', 5, 2, 1),
('502', 5, 1, 2),
('503', 5, 1, 1),
('504', 5, 2, 0),
('505', 5, 1, 1),
-- Floor 6
('601', 6, 2, 1),
('602', 6, 1, 1),
('603', 6, 3, 0),
('604', 6, 1, 1),
('605', 6, 2, 1);

-- =====================================================
-- USERS (Потребители)
-- =====================================================

-- Lecturers (Лектори) - password: lecturer123
INSERT INTO `user` (`faculty_number`, `full_name`, `email`, `password_hash`) VALUES
('LEC001', 'Проф. Иван Петров', 'ipetrov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('LEC002', 'Доц. Мария Георгиева', 'mgeorgieva@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('LEC003', 'Проф. Николай Димитров', 'ndimitrov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('LEC004', 'Доц. Елена Стоянова', 'estoyanova@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('LEC005', 'Гл. ас. Петър Иванов', 'pivanov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Assign lecturer roles
INSERT INTO `user_role` (`user_id`, `role`) VALUES
(2, 'LECTURER'),
(3, 'LECTURER'),
(4, 'LECTURER'),
(5, 'LECTURER'),
(6, 'LECTURER');

-- Assistants (Асистенти) - password: assistant123
INSERT INTO `user` (`faculty_number`, `full_name`, `email`, `password_hash`) VALUES
('AST001', 'Ас. Георги Николов', 'gnikolov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('AST002', 'Ас. Антония Маринова', 'amarinova@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('AST003', 'Ас. Димитър Стефанов', 'dstefanov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('AST004', 'Ас. Светлана Петрова', 'spetrova@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('AST005', 'Ас. Владимир Костов', 'vkostov@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('AST006', 'Ас. Ирина Тодорова', 'itodorova@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Assign assistant roles
INSERT INTO `user_role` (`user_id`, `role`) VALUES
(7, 'ASSISTANT'),
(8, 'ASSISTANT'),
(9, 'ASSISTANT'),
(10, 'ASSISTANT'),
(11, 'ASSISTANT'),
(12, 'ASSISTANT');

-- Students (Студенти) - password: student123
INSERT INTO `user` (`faculty_number`, `full_name`, `email`, `password_hash`) VALUES
('81234', 'Александър Тодоров', 'atodorov@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('81235', 'Виктория Димитрова', 'vdimitrova@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('81236', 'Борис Стоянов', 'bstoyanov@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('82134', 'Мария Иванова', 'mivanova@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('82135', 'Николай Георгиев', 'ngeorgiev@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Assign student roles and student data
INSERT INTO `user_role` (`user_id`, `role`) VALUES
(13, 'STUDENT'),
(14, 'STUDENT'),
(15, 'STUDENT'),
(16, 'STUDENT'),
(17, 'STUDENT');

INSERT INTO `student` (`user_id`, `major_id`, `year`, `stream_id`, `group_id`) VALUES
(13, 1, 2, 1, 1),  -- Информатика, 2 курс, Поток 1, Група 1
(14, 1, 2, 1, 2),  -- Информатика, 2 курс, Поток 1, Група 2
(15, 1, 2, 1, 3),  -- Информатика, 2 курс, Поток 1, Група 3
(16, 2, 1, 3, 7),  -- КН, 1 курс, Поток 1, Група 1
(17, 2, 1, 3, 8);  -- КН, 1 курс, Поток 1, Група 2

-- Student-assistant (student who is also an assistant)
INSERT INTO `user` (`faculty_number`, `full_name`, `email`, `password_hash`) VALUES
('82200', 'Стефан Колев', 'skolev@student.fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO `user_role` (`user_id`, `role`) VALUES
(18, 'STUDENT'),
(18, 'ASSISTANT');

INSERT INTO `student` (`user_id`, `major_id`, `year`, `stream_id`, `group_id`) VALUES
(18, 1, 4, 1, 1);  -- Информатика, 4 курс - also assistant

-- =====================================================
-- COURSES (Курсове)
-- =====================================================

-- Mandatory courses for Informatics (Информатика)
-- year = курс (1-5 за задължителни, NULL за избираеми)
INSERT INTO `course` (`id`, `code`, `name_bg`, `outline_bg`, `credits`, `year`, `is_elective`, `major_id`) VALUES
(1, 'INF101', 'Увод в програмирането', 
'1. Основни понятия в програмирането
2. Променливи и типове данни
3. Условни конструкции
4. Цикли
5. Масиви и низове
6. Функции
7. Рекурсия
8. Основни алгоритми за търсене и сортиране', 
6, 1, 0, 1),

(2, 'INF102', 'Обектно-ориентирано програмиране',
'1. Въведение в ООП
2. Класове и обекти
3. Наследяване
4. Полиморфизъм
5. Абстрактни класове и интерфейси
6. Изключения
7. Колекции
8. Шаблони за проектиране',
6, 1, 0, 1),

(3, 'INF201', 'Структури от данни и алгоритми',
'1. Анализ на алгоритми - O-нотация
2. Линейни структури - списъци, стекове, опашки
3. Дървета - BST, AVL, червено-черни
4. Хеширане
5. Графи - представяне и обхождане
6. Сортиране - бързо, сливане, heap
7. Динамично програмиране
8. Алчни алгоритми',
6, 2, 0, 1),

(4, 'INF202', 'Бази от данни',
'1. Релационен модел
2. SQL - DDL и DML
3. Нормализация
4. Индекси и оптимизация
5. Транзакции
6. Проектиране на БД
7. NoSQL бази данни',
5, 2, 0, 1);

-- Mandatory courses for Computer Science (КН)
INSERT INTO `course` (`id`, `code`, `name_bg`, `outline_bg`, `credits`, `year`, `is_elective`, `major_id`) VALUES
(5, 'CS101', 'Дискретна математика',
'1. Множества и релации
2. Комбинаторика
3. Теория на графите
4. Булева алгебра
5. Математическа логика
6. Теория на числата',
6, 1, 0, 2),

(6, 'CS201', 'Операционни системи',
'1. Въведение в ОС
2. Процеси и нишки
3. Планиране на процеси
4. Синхронизация
5. Управление на паметта
6. Файлови системи
7. Входно-изходни операции',
5, 2, 0, 2);

-- Elective courses (Избираеми) - year = NULL
INSERT INTO `course` (`id`, `code`, `name_bg`, `outline_bg`, `credits`, `year`, `is_elective`, `major_id`) VALUES
(7, 'ELC001', 'Уеб технологии',
'1. HTML5 и CSS3
2. JavaScript
3. PHP и сървърно програмиране
4. AJAX и REST API
5. Frameworks - Laravel, React
6. Сигурност в уеб
7. Performance оптимизация',
4, NULL, 1, NULL),

(8, 'ELC002', 'Машинно обучение',
'1. Въведение в ML
2. Линейна регресия
3. Класификация
4. Дървета за решения
5. Невронни мрежи
6. Deep Learning
7. Практически проекти',
4, NULL, 1, NULL),

(9, 'ELC003', 'Мобилни приложения',
'1. Android разработка
2. iOS разработка
3. Кросплатформени решения
4. UI/UX дизайн
5. Бази данни в мобилни приложения
6. Push нотификации
7. Публикуване в App Store/Play Store',
4, NULL, 1, NULL);

-- Course prerequisites
INSERT INTO `course_prerequisite` (`course_id`, `prereq_id`, `is_recommended`) VALUES
(2, 1, 0),  -- OOP requires Intro to Programming
(3, 1, 0),  -- Data Structures requires Intro to Programming
(3, 2, 1),  -- Data Structures - OOP is recommended
(4, 1, 0),  -- Databases requires Intro to Programming
(7, 1, 0),  -- Web Tech requires Intro to Programming
(8, 3, 0),  -- ML requires Data Structures
(9, 2, 0);  -- Mobile requires OOP

-- =====================================================
-- COURSE INSTANCES (Паралелки)
-- =====================================================

INSERT INTO `course_instance` (`id`, `course_id`, `academic_year`, `semester`, `exercise_count_per_week`, `test_count`, `exam_date_count`, `allow_test_during_lecture`, `test_duration_hours`, `exam_duration_hours`, `lecture_duration_hours`, `exercise_duration_hours`) VALUES
-- Winter semester mandatory courses
(1, 1, 2025, 'WINTER', 2, 2, 3, 0, 2, 3, 2, 2),  -- Intro to Programming
(2, 2, 2025, 'WINTER', 2, 2, 3, 0, 2, 3, 2, 2),  -- OOP
(3, 5, 2025, 'WINTER', 1, 1, 2, 1, 2, 2, 2, 2),  -- Discrete Math
-- Summer semester
(4, 3, 2025, 'SUMMER', 2, 2, 3, 0, 2, 3, 2, 2),  -- Data Structures
(5, 4, 2025, 'SUMMER', 2, 1, 2, 0, 2, 3, 2, 2),  -- Databases
(6, 6, 2025, 'SUMMER', 1, 1, 2, 0, 2, 3, 2, 2),  -- OS
-- Elective courses (both semesters)
(7, 7, 2025, 'WINTER', 1, 1, 1, 0, 2, 2, 2, 2),  -- Web Tech
(8, 8, 2025, 'SUMMER', 1, 0, 1, 0, 2, 2, 2, 2),  -- ML
(9, 9, 2025, 'WINTER', 1, 1, 1, 0, 2, 2, 2, 2);  -- Mobile

-- Assign lecturers to course instances
INSERT INTO `course_lecturer` (`course_instance_id`, `user_id`) VALUES
(1, 2),  -- Intro to Programming - Prof. Petrov
(1, 3),  -- Intro to Programming - Assoc. Prof. Georgieva
(2, 4),  -- OOP - Assoc. Prof. Stoyanova
(3, 2),  -- Discrete Math - Prof. Petrov
(4, 3),  -- Data Structures - Assoc. Prof. Georgieva
(5, 5),  -- Databases - Assist. Prof. Ivanov
(6, 4),  -- OS - Assoc. Prof. Stoyanova
(7, 5),  -- Web Tech - Assist. Prof. Ivanov
(8, 6),  -- ML - Assist. Prof.
(9, 6);  -- Mobile

-- Assign assistants to course instances
INSERT INTO `course_assistant` (`course_instance_id`, `user_id`) VALUES
(1, 7),
(1, 8),
(1, 9),
(1, 10),
(2, 7),
(2, 8),
(2, 11),
(2, 12),
(3, 9),
(3, 10),
(4, 7),
(4, 8),
(4, 9),
(4, 10),
(5, 11),
(5, 12),
(6, 7),
(6, 8),
(7, 18),  -- Student-assistant
(7, 11),
(8, 12),
(9, 18);  -- Student-assistant

-- Test ranges for course instances
INSERT INTO `test_range` (`course_instance_id`, `test_index`, `start_date`, `end_date`) VALUES
-- Intro to Programming tests
(1, 1, '2025-11-01', '2025-11-15'),
(1, 2, '2025-12-01', '2025-12-15'),
-- OOP tests
(2, 1, '2025-11-01', '2025-11-15'),
(2, 2, '2025-12-01', '2025-12-15'),
-- Discrete Math test
(3, 1, '2025-11-15', '2025-11-30'),
-- Data Structures tests
(4, 1, '2026-03-15', '2026-03-31'),
(4, 2, '2026-05-01', '2026-05-15'),
-- Databases test
(5, 1, '2026-04-01', '2026-04-15'),
-- OS test
(6, 1, '2026-04-15', '2026-04-30'),
-- Web Tech test
(7, 1, '2025-11-15', '2025-11-30'),
-- Mobile test
(9, 1, '2025-12-01', '2025-12-15');

-- User preferences (soft constraints)
INSERT INTO `user_preference` (`user_id`, `preference_type`, `priority`) VALUES
(2, 'MORNING', 8),      -- Prof. Petrov prefers morning
(3, 'AFTERNOON', 7),    -- Assoc. Prof. Georgieva prefers afternoon
(4, 'WHITE_BOARD', 6),  -- Assoc. Prof. Stoyanova prefers white board
(5, 'MORNING', 5),      -- Assist. Prof. Ivanov prefers morning
(7, 'AFTERNOON', 4),    -- Assistant Nikolov prefers afternoon
(18, 'AFTERNOON', 3);   -- Student-assistant prefers afternoon (not to conflict with classes)

-- Enrollments
INSERT INTO `enrollment` (`student_id`, `course_instance_id`) VALUES
-- Student 1 (INF) - mandatory + elective
(13, 1),
(13, 2),
(13, 7),
-- Student 2 (INF)
(14, 1),
(14, 2),
(14, 9),
-- Student 3 (INF)
(15, 1),
(15, 2),
-- Student 4 (CS)
(16, 3),
(16, 7),
-- Student 5 (CS)
(17, 3),
(17, 8),
-- Student-assistant
(18, 4),
(18, 5);

SET FOREIGN_KEY_CHECKS = 1;
