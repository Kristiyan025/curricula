-- =====================================================
-- Curricula Database Schema
-- MySQL 8.0+
-- Faculty of Mathematics and Informatics, Sofia University
-- Schedule Management System
-- =====================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS `log`;
DROP TABLE IF EXISTS `user_preference`;
DROP TABLE IF EXISTS `enrollment`;
DROP TABLE IF EXISTS `schedule_variant`;
DROP TABLE IF EXISTS `exam_schedule`;
DROP TABLE IF EXISTS `test_schedule`;
DROP TABLE IF EXISTS `weekly_slot`;
DROP TABLE IF EXISTS `test_range`;
DROP TABLE IF EXISTS `course_assistant`;
DROP TABLE IF EXISTS `course_lecturer`;
DROP TABLE IF EXISTS `course_instance`;
DROP TABLE IF EXISTS `course_prerequisite`;
DROP TABLE IF EXISTS `course`;
DROP TABLE IF EXISTS `student`;
DROP TABLE IF EXISTS `user_role`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `student_group`;
DROP TABLE IF EXISTS `major_stream`;
DROP TABLE IF EXISTS `major`;
DROP TABLE IF EXISTS `room`;
DROP TABLE IF EXISTS `academic_settings`;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Major (Специалност)
CREATE TABLE `major` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name_bg` VARCHAR(100) NOT NULL COMMENT 'Име на български (напр. Информатика)',
    `abbreviation` VARCHAR(10) NOT NULL COMMENT 'Съкращение (напр. INF)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_major_abbreviation` (`abbreviation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Major Stream (Поток)
CREATE TABLE `major_stream` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `major_id` INT UNSIGNED NOT NULL,
    `name_bg` VARCHAR(100) NOT NULL COMMENT 'Име на потока (напр. Поток 1)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_stream_name` (`major_id`, `name_bg`),
    KEY `idx_stream_major` (`major_id`),
    CONSTRAINT `fk_stream_major` FOREIGN KEY (`major_id`) REFERENCES `major` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student Group (Група)
CREATE TABLE `student_group` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stream_id` INT UNSIGNED NOT NULL,
    `name_bg` VARCHAR(50) NOT NULL COMMENT 'Наименование (напр. Група 1)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_name` (`stream_id`, `name_bg`),
    KEY `idx_group_stream` (`stream_id`),
    CONSTRAINT `fk_group_stream` FOREIGN KEY (`stream_id`) REFERENCES `major_stream` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User (Потребител)
CREATE TABLE `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `faculty_number` VARCHAR(10) NOT NULL COMMENT 'Факултетен номер',
    `full_name` VARCHAR(100) NOT NULL COMMENT 'Име и фамилия на български',
    `email` VARCHAR(100) DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL COMMENT 'Хеш на паролата',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_faculty_number` (`faculty_number`),
    KEY `idx_user_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Role (Потребителски роли)
CREATE TABLE `user_role` (
    `user_id` INT UNSIGNED NOT NULL,
    `role` ENUM('ADMIN', 'STUDENT', 'LECTURER', 'ASSISTANT') NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`, `role`),
    CONSTRAINT `fk_user_role_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Student (Данни за студент)
CREATE TABLE `student` (
    `user_id` INT UNSIGNED NOT NULL,
    `major_id` INT UNSIGNED NOT NULL,
    `year` INT NOT NULL COMMENT 'Курс (година 1-4)',
    `stream_id` INT UNSIGNED NOT NULL,
    `group_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    KEY `idx_student_major` (`major_id`),
    KEY `idx_student_stream` (`stream_id`),
    KEY `idx_student_group` (`group_id`),
    CONSTRAINT `fk_student_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_student_major` FOREIGN KEY (`major_id`) REFERENCES `major` (`id`),
    CONSTRAINT `fk_student_stream` FOREIGN KEY (`stream_id`) REFERENCES `major_stream` (`id`),
    CONSTRAINT `fk_student_group` FOREIGN KEY (`group_id`) REFERENCES `student_group` (`id`),
    CONSTRAINT `chk_student_year` CHECK (`year` BETWEEN 1 AND 4)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Room (Аудитория)
CREATE TABLE `room` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `number` VARCHAR(3) NOT NULL COMMENT 'Трицифрен код (първа цифра = етаж)',
    `floor` INT NOT NULL COMMENT 'Етаж (1-6)',
    `white_boards` INT NOT NULL DEFAULT 1 COMMENT 'Бели дъски',
    `black_boards` INT NOT NULL DEFAULT 0 COMMENT 'Черни дъски',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_room_number` (`number`),
    CONSTRAINT `chk_room_floor` CHECK (`floor` BETWEEN 1 AND 6),
    CONSTRAINT `chk_room_white_boards` CHECK (`white_boards` >= 1),
    CONSTRAINT `chk_room_black_boards` CHECK (`black_boards` >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course (Курс)
CREATE TABLE `course` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(10) NOT NULL COMMENT 'Код (напр. INF101)',
    `name_bg` VARCHAR(200) NOT NULL COMMENT 'Име на български',
    `outline_bg` TEXT NOT NULL COMMENT 'Конспект на български',
    `credits` INT NOT NULL COMMENT 'Кредити',
    `year` INT DEFAULT NULL COMMENT 'Курс (година 1-5), NULL за избираеми',
    `is_elective` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Избираем курс',
    `major_id` INT UNSIGNED DEFAULT NULL COMMENT 'За задължителни курсове - специалност',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_course_code` (`code`),
    KEY `idx_course_major` (`major_id`),
    KEY `idx_course_year` (`year`),
    CONSTRAINT `fk_course_major` FOREIGN KEY (`major_id`) REFERENCES `major` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_course_credits` CHECK (`credits` > 0),
    CONSTRAINT `chk_course_year` CHECK (`year` IS NULL OR `year` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Prerequisite (Предварителни изисквания)
CREATE TABLE `course_prerequisite` (
    `course_id` INT UNSIGNED NOT NULL,
    `prereq_id` INT UNSIGNED NOT NULL,
    `is_recommended` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'FALSE = задължително',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`course_id`, `prereq_id`),
    KEY `idx_prereq_prereq` (`prereq_id`),
    CONSTRAINT `fk_prereq_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_prereq_prereq` FOREIGN KEY (`prereq_id`) REFERENCES `course` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Instance (Паралелка)
CREATE TABLE `course_instance` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_id` INT UNSIGNED NOT NULL,
    `academic_year` YEAR NOT NULL COMMENT 'Учебна година (напр. 2025)',
    `semester` ENUM('WINTER', 'SUMMER') NOT NULL COMMENT 'Семестър',
    `exercise_count_per_week` INT NOT NULL DEFAULT 1 COMMENT 'Упражнения на седмица',
    `test_count` INT NOT NULL DEFAULT 0 COMMENT 'Брой контролни',
    `exam_date_count` INT NOT NULL DEFAULT 1 COMMENT 'Брой дати за изпит',
    `allow_test_during_lecture` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Контролно по време на лекция',
    `test_duration_hours` INT NOT NULL DEFAULT 2 COMMENT 'Продължителност на контролно (часове)',
    `exam_duration_hours` INT NOT NULL DEFAULT 3 COMMENT 'Продължителност на изпит (часове)',
    `lecture_duration_hours` INT NOT NULL DEFAULT 2 COMMENT 'Продължителност на лекция (часове)',
    `exercise_duration_hours` INT NOT NULL DEFAULT 2 COMMENT 'Продължителност на упражнение (часове)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_instance` (`course_id`, `academic_year`, `semester`),
    KEY `idx_instance_semester` (`semester`, `academic_year`),
    CONSTRAINT `fk_instance_course` FOREIGN KEY (`course_id`) REFERENCES `course` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_instance_exercise` CHECK (`exercise_count_per_week` BETWEEN 1 AND 6),
    CONSTRAINT `chk_instance_test` CHECK (`test_count` >= 0),
    CONSTRAINT `chk_instance_exam` CHECK (`exam_date_count` BETWEEN 0 AND 3),
    CONSTRAINT `chk_instance_test_duration` CHECK (`test_duration_hours` BETWEEN 1 AND 5),
    CONSTRAINT `chk_instance_exam_duration` CHECK (`exam_duration_hours` BETWEEN 1 AND 5),
    CONSTRAINT `chk_instance_lecture_duration` CHECK (`lecture_duration_hours` BETWEEN 1 AND 5),
    CONSTRAINT `chk_instance_exercise_duration` CHECK (`exercise_duration_hours` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Lecturer (Лектори)
CREATE TABLE `course_lecturer` (
    `course_instance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`course_instance_id`, `user_id`),
    KEY `idx_lecturer_user` (`user_id`),
    CONSTRAINT `fk_lecturer_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_lecturer_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course Assistant (Асистенти)
CREATE TABLE `course_assistant` (
    `course_instance_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`course_instance_id`, `user_id`),
    KEY `idx_assistant_user` (`user_id`),
    CONSTRAINT `fk_assistant_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_assistant_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test Range (Допустим период за контролно)
CREATE TABLE `test_range` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_instance_id` INT UNSIGNED NOT NULL,
    `test_index` INT NOT NULL COMMENT '1..test_count',
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_test_range` (`course_instance_id`, `test_index`),
    CONSTRAINT `fk_test_range_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_test_range_dates` CHECK (`end_date` >= `start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SCHEDULE TABLES
-- =====================================================

-- Weekly Slot (Седмичен слот)
CREATE TABLE `weekly_slot` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_instance_id` INT UNSIGNED NOT NULL,
    `day_of_week` ENUM('MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN') NOT NULL,
    `start_time` TIME NOT NULL COMMENT 'Кръгъл час',
    `end_time` TIME NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `slot_type` ENUM('LECTURE', 'EXERCISE') NOT NULL,
    `group_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL за лекции',
    `assistant_id` INT UNSIGNED DEFAULT NULL COMMENT 'За упражнения - ID на асистента',
    `variant_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID на варианта (NULL = избран)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_slot_instance` (`course_instance_id`),
    KEY `idx_slot_room` (`room_id`),
    KEY `idx_slot_group` (`group_id`),
    KEY `idx_slot_day_time` (`day_of_week`, `start_time`),
    KEY `idx_slot_variant` (`variant_id`),
    CONSTRAINT `fk_slot_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slot_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`id`),
    CONSTRAINT `fk_slot_group` FOREIGN KEY (`group_id`) REFERENCES `student_group` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_slot_assistant` FOREIGN KEY (`assistant_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    CONSTRAINT `chk_slot_times` CHECK (`end_time` > `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Test Schedule (График на контролни)
CREATE TABLE `test_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_instance_id` INT UNSIGNED NOT NULL,
    `test_index` INT NOT NULL COMMENT '1..test_count',
    `date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID на варианта (NULL = избран)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_test_instance` (`course_instance_id`),
    KEY `idx_test_date` (`date`),
    KEY `idx_test_room` (`room_id`),
    KEY `idx_test_variant` (`variant_id`),
    CONSTRAINT `fk_test_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_test_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`id`),
    CONSTRAINT `chk_test_times` CHECK (`end_time` > `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exam Schedule (График на изпити)
CREATE TABLE `exam_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `course_instance_id` INT UNSIGNED NOT NULL,
    `exam_index` INT NOT NULL COMMENT '1..exam_date_count',
    `date` DATE NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `room_id` INT UNSIGNED NOT NULL,
    `variant_id` INT UNSIGNED DEFAULT NULL COMMENT 'ID на варианта (NULL = избран)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_exam_instance` (`course_instance_id`),
    KEY `idx_exam_date` (`date`),
    KEY `idx_exam_room` (`room_id`),
    KEY `idx_exam_variant` (`variant_id`),
    CONSTRAINT `fk_exam_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_exam_room` FOREIGN KEY (`room_id`) REFERENCES `room` (`id`),
    CONSTRAINT `chk_exam_times` CHECK (`end_time` > `start_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Schedule Variant (Вариант на разписание)
CREATE TABLE `schedule_variant` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `type` ENUM('WEEKLY', 'TEST', 'EXAM') NOT NULL,
    `semester` ENUM('WINTER', 'SUMMER') NOT NULL,
    `academic_year` YEAR NOT NULL,
    `session_type` ENUM('WINTER_EXAM', 'SUMMER_EXAM', 'RESIT') DEFAULT NULL COMMENT 'За изпитни графици',
    `name` VARCHAR(50) NOT NULL COMMENT 'Име на варианта (A, B, C)',
    `fitness_score` DECIMAL(10,4) DEFAULT NULL COMMENT 'Оценка на генетичния алгоритъм',
    `is_selected` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Избран ли е вариантът',
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `selected_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_variant_type` (`type`, `semester`, `academic_year`),
    KEY `idx_variant_selected` (`is_selected`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- SETTINGS & LOG TABLES
-- =====================================================

-- Academic Settings (Настройки за учебна година)
CREATE TABLE `academic_settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `setting_key` VARCHAR(50) NOT NULL,
    `setting_value` VARCHAR(255) NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Preference (Предпочитания на потребител - меки ограничения)
CREATE TABLE `user_preference` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` INT UNSIGNED NOT NULL,
    `preference_type` ENUM('MORNING', 'AFTERNOON', 'WHITE_BOARD', 'NO_BLACK_BOARD') NOT NULL,
    `priority` INT NOT NULL DEFAULT 1 COMMENT 'Приоритет (1-10)',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_pref` (`user_id`, `preference_type`),
    CONSTRAINT `fk_pref_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `chk_pref_priority` CHECK (`priority` BETWEEN 1 AND 10)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Enrollment (Записване на студент в курс)
CREATE TABLE `enrollment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id` INT UNSIGNED NOT NULL,
    `course_instance_id` INT UNSIGNED NOT NULL,
    `enrolled_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_enrollment` (`student_id`, `course_instance_id`),
    KEY `idx_enrollment_course` (`course_instance_id`),
    CONSTRAINT `fk_enrollment_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_enrollment_instance` FOREIGN KEY (`course_instance_id`) REFERENCES `course_instance` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Log (Журнал на събитията)
CREATE TABLE `log` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `message_code` VARCHAR(50) NOT NULL COMMENT 'Код на събитието',
    `parameters` JSON DEFAULT NULL COMMENT 'Параметри (JSON)',
    PRIMARY KEY (`id`),
    KEY `idx_log_code` (`message_code`),
    KEY `idx_log_timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- INITIAL DATA
-- =====================================================

-- Insert default academic settings
INSERT INTO `academic_settings` (`setting_key`, `setting_value`) VALUES
('current_phase', 'WINTER_SEMESTER'),
('academic_year', '2025');

-- Insert default admin user (password: admin123)
INSERT INTO `user` (`faculty_number`, `full_name`, `email`, `password_hash`) VALUES
('ADMIN001', 'Администратор на системата', 'admin@fmi.uni-sofia.bg', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO `user_role` (`user_id`, `role`) VALUES
(1, 'ADMIN');

SET FOREIGN_KEY_CHECKS = 1;
