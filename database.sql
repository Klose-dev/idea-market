-- =============================================
--  IDEA MARKET — DATABASE SCHEMA
--  Run this in MySQL/phpMyAdmin
-- =============================================

CREATE DATABASE IF NOT EXISTS idea_market CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE idea_market;

-- ── Users ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(120) NOT NULL,
    email         VARCHAR(180) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role          ENUM('admin','user','investor') NOT NULL DEFAULT 'user',
    avatar        VARCHAR(255) NULL,
    bio           TEXT NULL,
    is_banned     TINYINT(1) NOT NULL DEFAULT 0,
    last_login    DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_email (email)
) ENGINE=InnoDB;

-- ── Categories ────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    id    SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name  VARCHAR(80) NOT NULL,
    slug  VARCHAR(80) NOT NULL UNIQUE,
    icon  VARCHAR(40) NOT NULL DEFAULT 'fa-lightbulb',
    color VARCHAR(20) NOT NULL DEFAULT '#2563eb'
) ENGINE=InnoDB;

INSERT INTO categories (name, slug, icon, color) VALUES
('Technology',   'technology',  'fa-microchip',       '#2563eb'),
('Social',       'social',      'fa-users',           '#f97316'),
('Health',       'health',      'fa-heartbeat',       '#dc2626'),
('Education',    'education',   'fa-graduation-cap',  '#7c3aed'),
('Environment',  'environment', 'fa-leaf',            '#16a34a'),
('Finance',      'finance',     'fa-chart-line',      '#f59e0b'),
('Arts & Media', 'arts',        'fa-palette',         '#ec4899'),
('Other',        'other',       'fa-lightbulb',       '#64748b')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- ── Ideas ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS ideas (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id        VARCHAR(20) NOT NULL UNIQUE,
    user_id        INT UNSIGNED NOT NULL,
    category_id    SMALLINT UNSIGNED NOT NULL DEFAULT 8,
    title          VARCHAR(255) NOT NULL,
    tagline        VARCHAR(255) NULL,
    description    TEXT NOT NULL,
    problem        TEXT NULL,
    solution       TEXT NULL,
    target_market  TEXT NULL,
    funding_goal   DECIMAL(12,2) NULL,
    funding_raised DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    status         ENUM('pending','review','approved','funded','closed') NOT NULL DEFAULT 'pending',
    priority       ENUM('high','medium','low') NOT NULL DEFAULT 'medium',
    assigned_to    INT UNSIGNED NULL,
    vote_count     INT UNSIGNED NOT NULL DEFAULT 0,
    view_count     INT UNSIGNED NOT NULL DEFAULT 0,
    is_featured    TINYINT(1) NOT NULL DEFAULT 0,
    tags           VARCHAR(500) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status   (status),
    INDEX idx_user     (user_id),
    INDEX idx_category (category_id),
    INDEX idx_created  (created_at),
    INDEX idx_featured (is_featured)
) ENGINE=InnoDB;

-- ── Votes ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS votes (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_vote (idea_id, user_id),
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Comments ──────────────────────────────────
CREATE TABLE IF NOT EXISTS comments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id    INT UNSIGNED NOT NULL,
    user_id    INT UNSIGNED NOT NULL,
    parent_id  INT UNSIGNED NULL,
    body       TEXT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)   REFERENCES ideas(id)    ON DELETE CASCADE,
    FOREIGN KEY (user_id)   REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE,
    INDEX idx_idea (idea_id)
) ENGINE=InnoDB;

-- ── Investments ───────────────────────────────
CREATE TABLE IF NOT EXISTS investments (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id    INT UNSIGNED NOT NULL,
    investor_id INT UNSIGNED NOT NULL,
    amount     DECIMAL(12,2) NOT NULL,
    message    TEXT NULL,
    status     ENUM('pledged','confirmed','withdrawn') NOT NULL DEFAULT 'pledged',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)     REFERENCES ideas(id)  ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES users(id)  ON DELETE CASCADE,
    INDEX idx_idea     (idea_id),
    INDEX idx_investor (investor_id)
) ENGINE=InnoDB;

-- ── Activity Log ──────────────────────────────
CREATE TABLE IF NOT EXISTS activity_log (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id      INT UNSIGNED NOT NULL,
    action       VARCHAR(255) NOT NULL,
    performed_by INT UNSIGNED NOT NULL,
    status       VARCHAR(40) NULL,
    timestamp    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (idea_id)      REFERENCES ideas(id) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_idea      (idea_id),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB;

-- ── Notifications ─────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    idea_id    INT UNSIGNED NULL,
    type       VARCHAR(60) NOT NULL,
    message    VARCHAR(500) NOT NULL,
    is_read    TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (idea_id) REFERENCES ideas(id) ON DELETE CASCADE,
    INDEX idx_user   (user_id),
    INDEX idx_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ── Sample admin user (password: Admin1234!) ──
INSERT IGNORE INTO users (full_name, email, password_hash, role) VALUES
('Admin User', 'admin@ideamarket.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ── Sample ideas ──────────────────────────────
INSERT IGNORE INTO ideas (idea_id, user_id, category_id, title, tagline, description, status, priority, vote_count, is_featured) VALUES
('IDEA-00000001', 1, 1, 'AI-Powered Study Assistant', 'Learn smarter, not harder', 'An AI assistant that adapts to each student learning style and pace.', 'approved', 'high', 142, 1),
('IDEA-00000002', 1, 5, 'Community Solar Grid', 'Clean energy for all neighborhoods', 'A peer-to-peer solar energy sharing platform for residential communities.', 'funded', 'high', 98, 1),
('IDEA-00000003', 1, 2, 'Skill Barter Network', 'Trade your skills, grow together', 'A marketplace where people exchange skills instead of money.', 'review', 'medium', 67, 0),
('IDEA-00000004', 1, 3, 'Remote Health Monitor', 'Your health, always connected', 'Wearable device + app for continuous health monitoring with AI insights.', 'pending', 'high', 34, 0);
