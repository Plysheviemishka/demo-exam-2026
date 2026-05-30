CREATE DATABASE IF NOT EXISTS uchus_rf
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE uchus_rf;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS status_history;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS payment_methods;
DROP TABLE IF EXISTS application_statuses;
DROP TABLE IF EXISTS course_types;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(180) NOT NULL,
    phone VARCHAR(40) NOT NULL,
    email VARCHAR(120) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_users_login CHECK (login REGEXP '^[A-Za-z0-9]{6,}$')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE course_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(120) NOT NULL UNIQUE,
    description TEXT NULL,
    duration_hours SMALLINT UNSIGNED NOT NULL DEFAULT 72,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_course_types_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payment_methods (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(80) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_payment_methods_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE application_statuses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(30) NOT NULL UNIQUE,
    sort_order TINYINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    course_type_id INT UNSIGNED NOT NULL,
    start_date DATE NOT NULL,
    payment_method_id INT UNSIGNED NOT NULL,
    status_id INT UNSIGNED NOT NULL,
    comment VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_applications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_applications_course FOREIGN KEY (course_type_id) REFERENCES course_types(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_payment FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id) ON DELETE RESTRICT,
    CONSTRAINT fk_applications_status FOREIGN KEY (status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT,
    INDEX idx_applications_user_id (user_id),
    INDEX idx_applications_status_id (status_id),
    INDEX idx_applications_payment_method_id (payment_method_id),
    INDEX idx_applications_start_date (start_date),
    INDEX idx_applications_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE reviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL UNIQUE,
    user_id INT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    text TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_reviews_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5),
    INDEX idx_reviews_user_id (user_id),
    FULLTEXT INDEX ft_reviews_text (text)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    old_status_id INT UNSIGNED NULL,
    new_status_id INT UNSIGNED NOT NULL,
    changed_by VARCHAR(50) NOT NULL DEFAULT 'Admin26',
    changed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_history_application FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_old_status FOREIGN KEY (old_status_id) REFERENCES application_statuses(id) ON DELETE SET NULL,
    CONSTRAINT fk_status_history_new_status FOREIGN KEY (new_status_id) REFERENCES application_statuses(id) ON DELETE RESTRICT,
    INDEX idx_status_history_application (application_id),
    INDEX idx_status_history_changed_at (changed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO course_types (title, description, duration_hours) VALUES
('Повышение квалификации', 'Краткосрочные программы для обновления профессиональных компетенций.', 72),
('Профессиональная переподготовка', 'Программы для получения новой профессиональной квалификации.', 256),
('Охрана труда', 'Обучение требованиям охраны труда и безопасной организации работ.', 40);

INSERT INTO payment_methods (title, description) VALUES
('Банковская карта', 'Оплата банковской картой на сайте.'),
('СБП', 'Оплата через систему быстрых платежей.'),
('Счет для организации', 'Выставление счета для юридического лица.');

INSERT INTO application_statuses (title, code, sort_order) VALUES
('Новая', 'new', 1),
('Идет обучение', 'in_progress', 2),
('Обучение завершено', 'done', 3);
