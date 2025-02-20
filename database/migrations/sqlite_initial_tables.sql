-- ユーザーテーブル
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    reset_token TEXT DEFAULT NULL,
    reset_token_expires_at DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL
);
CREATE INDEX IF NOT EXISTS idx_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_reset_token ON users(reset_token);

-- プロジェクトテーブル
CREATE TABLE IF NOT EXISTS projects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'planning' CHECK(status IN ('planning', 'active', 'on_hold', 'completed', 'cancelled')),
    start_date DATE NOT NULL,
    end_date DATE DEFAULT NULL,
    created_by INTEGER NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_created_by ON projects(created_by);
CREATE INDEX IF NOT EXISTS idx_status ON projects(status);
CREATE INDEX IF NOT EXISTS idx_dates ON projects(start_date, end_date);

-- プロジェクトメンバーテーブル
CREATE TABLE IF NOT EXISTS project_members (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    role TEXT NOT NULL DEFAULT 'member' CHECK(role IN ('owner', 'admin', 'member')),
    created_at DATETIME NOT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    UNIQUE(project_id, user_id)
);
CREATE INDEX IF NOT EXISTS idx_project_role ON project_members(project_id, role);

-- タスクテーブル
CREATE TABLE IF NOT EXISTS tasks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    project_id INTEGER,
    title TEXT NOT NULL,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'in_progress', 'completed', 'cancelled')),
    priority TEXT NOT NULL DEFAULT 'medium' CHECK(priority IN ('low', 'medium', 'high')),
    progress INTEGER DEFAULT 0 CHECK(progress >= 0 AND progress <= 100),
    due_date DATE DEFAULT NULL,
    user_id INTEGER,
    created_by INTEGER NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    FOREIGN KEY (project_id) REFERENCES projects(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
CREATE INDEX IF NOT EXISTS idx_project_status ON tasks(project_id, status);
CREATE INDEX IF NOT EXISTS idx_user_status ON tasks(user_id, status);
CREATE INDEX IF NOT EXISTS idx_priority_due_date ON tasks(priority, due_date);

-- サンプルデータの投入
INSERT INTO users (name, email, password, created_at, updated_at) VALUES
('管理者', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', datetime('now'), datetime('now')),
('テストユーザー', 'user@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', datetime('now'), datetime('now'));

INSERT INTO projects (name, description, status, start_date, created_by, created_at, updated_at) VALUES
('サンプルプロジェクト', 'これはサンプルプロジェクトです。', 'active', date('now'), 1, datetime('now'), datetime('now'));

INSERT INTO project_members (project_id, user_id, role, created_at) VALUES
(1, 1, 'owner', datetime('now')),
(1, 2, 'member', datetime('now'));

INSERT INTO tasks (project_id, title, description, status, priority, user_id, created_by, created_at, updated_at) VALUES
(1, 'サンプルタスク1', 'これはサンプルタスク1です。', 'pending', 'medium', 2, 1, datetime('now'), datetime('now')),
(1, 'サンプルタスク2', 'これはサンプルタスク2です。', 'in_progress', 'high', 1, 1, datetime('now'), datetime('now'));
