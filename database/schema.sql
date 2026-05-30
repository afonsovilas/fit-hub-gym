-- Executar para repor/inicializar a base de dados:
--   sqlite3 database/database.db < database/schema.sql

PRAGMA foreign_keys = ON;

DROP TABLE IF EXISTS nutrition_requests;
DROP TABLE IF EXISTS progress_logs;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS personal_training_bookings;
DROP TABLE IF EXISTS class_waitlists;
DROP TABLE IF EXISTS reviews;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS classes;
DROP TABLE IF EXISTS equipment;
DROP TABLE IF EXISTS trainer_specializations;
DROP TABLE IF EXISTS trainer_profiles;
DROP TABLE IF EXISTS users;

-- ──────────────────────────────────────────────────────────────────────────────
-- 1. Utilizadores (users)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    name          TEXT    NOT NULL,
    email         TEXT    NOT NULL UNIQUE,
    username      TEXT    UNIQUE,
    password_hash TEXT    NOT NULL,
    role          TEXT    NOT NULL DEFAULT 'member'
                          CHECK(role IN ('member', 'trainer', 'admin')),
    phone         TEXT,
    avatar        TEXT,
    is_active     INTEGER NOT NULL DEFAULT 1 
                          CHECK(is_active IN (0, 1)),
    created_at    TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 2. Perfis de Treinadores (trainer_profiles)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trainer_profiles (
    user_id        INTEGER PRIMARY KEY REFERENCES users(id) ON DELETE CASCADE,
    bio            TEXT,
    certifications TEXT
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 3. Especializações de Treinadores (trainer_specializations)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trainer_specializations (
    trainer_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    specialization TEXT NOT NULL,
    PRIMARY KEY (trainer_id, specialization)
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 5. Equipamento (equipment) na zona de treino livre
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS equipment (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    name        TEXT    NOT NULL,
    category    TEXT    NOT NULL,
    status      TEXT    NOT NULL DEFAULT 'available'
                        CHECK(status IN ('available', 'maintenance', 'in_use')),
    description TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 6. Aulas Agendadas (classes)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS classes (
    id           INTEGER PRIMARY KEY AUTOINCREMENT,
    name         TEXT    NOT NULL,
    description  TEXT,
    instructor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE RESTRICT,
    capacity     INTEGER NOT NULL DEFAULT 20,
    scheduled_at TEXT    NOT NULL,
    duration_min INTEGER NOT NULL DEFAULT 60,
    room         TEXT    NOT NULL DEFAULT 'Sala 1',
    created_at   TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 7. Inscrições / Marcações de Aulas (bookings)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS bookings (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
    class_id   INTEGER NOT NULL REFERENCES classes(id)  ON DELETE CASCADE,
    booked_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE(user_id, class_id)
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 8. Avaliações de Aulas (reviews)
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS reviews (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
    class_id   INTEGER NOT NULL REFERENCES classes(id)  ON DELETE CASCADE,
    rating     INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
    comment    TEXT,
    created_at TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE(user_id, class_id)
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 9. Listas de Espera de Aulas (class_waitlists) - Extra
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS class_waitlists (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id)    ON DELETE CASCADE,
    class_id   INTEGER NOT NULL REFERENCES classes(id)  ON DELETE CASCADE,
    joined_at  TEXT    NOT NULL DEFAULT (datetime('now')),
    UNIQUE(user_id, class_id)
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 10. Sessões Individuais de Personal Training (personal_training_bookings) - Extra
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS personal_training_bookings (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    trainer_id  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    scheduled_at TEXT   NOT NULL,
    status      TEXT    NOT NULL DEFAULT 'pending'
                        CHECK(status IN ('pending', 'confirmed', 'cancelled', 'rejected')),
    notes       TEXT,
    rejection_reason TEXT,
    created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 11. Notificações do Sistema (notifications) - Extra
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id         INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id    INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    message    TEXT    NOT NULL,
    is_read    INTEGER NOT NULL DEFAULT 0 CHECK(is_read IN (0, 1)),
    created_at TEXT    NOT NULL DEFAULT (datetime('now'))
);

-- ──────────────────────────────────────────────────────────────────────────────
-- 12. Registo de Progresso Físico dos Membros (progress_logs) - Extra
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS progress_logs (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id       INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    weight        REAL,
    body_fat      REAL,
    workout_notes TEXT,
    logged_at     TEXT    NOT NULL DEFAULT (datetime('now'))
);


-- ──────────────────────────────────────────────────────────────────────────────
-- 13. Pedidos de Planos de Nutrição (nutrition_requests) - Extra
-- ──────────────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS nutrition_requests (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    member_id      INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    trainer_id     INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    status         TEXT NOT NULL DEFAULT 'pending' CHECK(status IN ('pending', 'accepted', 'completed')),
    scheduled_date TEXT,
    plan_details   TEXT,
    created_at     TEXT NOT NULL DEFAULT (datetime('now'))
);



-- ──────────────────────────────────────────────────────────────────────────────
-- DADOS PILOTO / SEED DATA
-- ──────────────────────────────────────────────────────────────────────────────

-- Inserir Treinadores
INSERT INTO users (id, name, email, username, password_hash, role, phone, avatar, is_active) VALUES
(1, 'João Silva', 'joao@fithub.pt', 'joaosilva', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'trainer', '+351 911 222 333', 'assets/images/trainers/joao.jpg', 1),
(2, 'Maria Santos', 'maria@fithub.pt', 'mariasantos', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'trainer', '+351 912 333 444', 'assets/images/trainers/maria.jpg', 1),
(3, 'Carlos Mendes', 'carlos@fithub.pt', 'carlosmendes', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'trainer', '+351 913 444 555', 'assets/images/trainers/carlos.jpg', 1),
(4, 'Ana Pereira', 'ana@fithub.pt', 'anapereira', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'trainer', '+351 914 555 666', 'assets/images/trainers/ana.jpg', 0);

-- Inserir Perfis de Treinadores
INSERT INTO trainer_profiles (user_id, bio, certifications) VALUES
(1, 'Especialista em Yoga e bem-estar corporal, com mais de 8 anos de experiência internacional.', 'Certificação Internacional RYT-500 em Hatha e Vinyasa Yoga, Curso de Meditação Mindfulness.'),
(2, 'Apaixonada por movimento e reabilitação física. Especializada em ajudar membros a atingirem os seus objetivos de força e postura.', 'Licenciatura em Ciências do Desporto, Certificação Oficial Pilates Clínico, Certificação Spinning Nível 3.'),
(3, 'Treinador de alta performance focado em resultados rápidos, HIIT e desportos de combate.', 'Certificação HIIT & Metabolic Training, Treinador Nível II de Boxe (FPB), Certificação Personal Training.'),
(4, 'Foco em saúde e longevidade através do treino de força estruturado e nutrição básica.', 'Licenciatura em Educação Física, Certificação em Reabilitação Musculoesquelética.');

-- Inserir Especializações
INSERT INTO trainer_specializations (trainer_id, specialization) VALUES
(1, 'yoga'),
(1, 'personal'),
(2, 'pilates'),
(2, 'spinning'),
(2, 'personal'),
(3, 'hiit'),
(3, 'boxing'),
(4, 'personal'),
(1, 'nutricao'),
(2, 'nutricao');

-- Inserir Administrador
INSERT INTO users (id, name, email, username, password_hash, role, phone, is_active) VALUES
(8, 'Administrador FitHub', 'admin@fithub.pt', 'admin', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'admin', '+351 900 000 000', 1);

-- Inserir Membro Aluno Piloto e mais 4 membros fictícios
INSERT INTO users (id, name, email, username, password_hash, role, phone, is_active) VALUES
(9, 'Aluno Piloto', 'aluno@fithub.pt', 'alunopiloto', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'member', '+351 931 111 222', 1),
(10, 'Bernardo Silva', 'bernardo@fithub.pt', 'bernardosilva', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'member', '+351 932 222 333', 1),
(11, 'Diana Costa', 'diana@fithub.pt', 'dianacosta', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'member', '+351 933 333 444', 1),
(12, 'Eduardo Santos', 'eduardo@fithub.pt', 'eduardosantos', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'member', '+351 934 444 555', 1),
(13, 'Filipa Martins', 'filipa@fithub.pt', 'filipamartins', '$2y$10$/n8a2FkYESFRYPFGVr2yXesHeM6JTY4wYdWcL7mu/j9z82mH.ZyeO', 'member', '+351 935 555 666', 1);

-- Inserir Equipamentos
INSERT INTO equipment (id, name, category, status, description) VALUES
(1, 'Passadeira Matrix A', 'Cardio', 'available', 'Passadeira com ecrã tátil e simulação de percursos reais.'),
(2, 'Bicicleta Spinning Tomahawk 1', 'Cardio', 'available', 'Bicicleta de ciclismo indoor com regulação de potência eletrónica.'),
(3, 'Banco Supino Olímpico', 'Strength', 'available', 'Banco reforçado para supino horizontal com barra olímpica.'),
(4, 'Press de Pernas 45º', 'Strength', 'available', 'Prensa robusta articulada com carga por discos de ferro.'),
(5, 'Passadeira Matrix B', 'Cardio', 'maintenance', 'Em manutenção preventiva para lubrificação de fita e calibração de motor.'),
(6, 'Conjunto Halteres de Uretano 2-30kg', 'Strength', 'in_use', 'Halteres dispostos na zona de pesos livres.');

-- Inserir Aulas
INSERT INTO classes (id, name, description, instructor_id, capacity, scheduled_at, duration_min, room) VALUES
(1, 'Yoga Matinal', 'Uma aula relaxante de yoga para começar o seu dia com energia e equilíbrio.', 1, 20, '2026-05-27 07:00', 60, 'Sala 1'),
(2, 'Spinning Intenso', 'Aula de spinning de alta intensidade para queimar calorias e melhorar resistência.', 2, 15, '2026-05-28 18:30', 60, 'Sala 2'),
(3, 'HIIT Express', 'Treino de alta intensidade com intervalos para máxima eficácia em pouco tempo.', 3, 20, '2026-05-27 19:00', 45, 'Sala 3'),
(4, 'Pilates Clássico', 'Aula de pilates focada em core, flexibilidade e fortalecimento muscular.', 2, 15, '2026-05-28 10:00', 60, 'Sala 1'),
(5, 'Zumba Party', 'Dança e diversão! Aula energética de zumba para queimar calorias e se divertir.', 1, 25, '2026-05-27 20:00', 60, 'Sala 2'),
(6, 'Box Training', 'Treino de boxe para melhorar coordenação, força e resistência cardiovascular.', 3, 10, '2026-05-28 17:00', 60, 'Sala 3');

-- Inserir Marcações (Bookings)
-- Aula 1 (Yoga) - 12 Inscrições
INSERT INTO bookings (user_id, class_id) VALUES
(9, 1), (10, 1), (11, 1), (12, 1), (13, 1);
-- Inserir mais alguns registos simulando outros membros
INSERT INTO bookings (user_id, class_id) SELECT id, 1 FROM users WHERE id NOT IN (1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13) LIMIT 7;

-- Aula 2 (Spinning) - 15 Inscrições (Aula cheia!)
INSERT INTO bookings (user_id, class_id) VALUES
(9, 2), (10, 2), (11, 2), (12, 2), (13, 2);
INSERT INTO bookings (user_id, class_id) SELECT id, 2 FROM users WHERE id NOT IN (9, 10, 11, 12, 13) LIMIT 10;

-- Aula 3 (HIIT) - 8 Inscrições
INSERT INTO bookings (user_id, class_id) VALUES
(9, 3), (10, 3), (11, 3);
INSERT INTO bookings (user_id, class_id) SELECT id, 3 FROM users WHERE id NOT IN (9, 10, 11) LIMIT 5;

-- Aula 4 (Pilates) - 10 Inscrições
INSERT INTO bookings (user_id, class_id) VALUES
(9, 4), (11, 4), (12, 4);
INSERT INTO bookings (user_id, class_id) SELECT id, 4 FROM users WHERE id NOT IN (9, 11, 12) LIMIT 7;

-- Aula 5 (Zumba) - 7 Inscrições
INSERT INTO bookings (user_id, class_id) VALUES
(9, 5), (10, 5), (13, 5);
INSERT INTO bookings (user_id, class_id) SELECT id, 5 FROM users WHERE id NOT IN (9, 10, 13) LIMIT 4;

-- Aula 6 (Boxe) - 4 Inscrições
INSERT INTO bookings (user_id, class_id) VALUES
(9, 6), (12, 6);
INSERT INTO bookings (user_id, class_id) SELECT id, 6 FROM users WHERE id NOT IN (9, 12) LIMIT 2;

-- Inserir Reviews piloto
INSERT INTO reviews (user_id, class_id, rating, comment) VALUES
(9, 1, 5, 'Excelente aula de yoga, senti-me super relaxado e focado. Recomendo o treinador João!'),
(10, 1, 4, 'Muito bom para acalmar a mente ao início do dia.'),
(11, 3, 5, 'Intenso e rápido. Ideal para quem tem pouco tempo e quer dar o máximo!'),
(12, 4, 4, 'Ótimo treino de core e postura. A instrutora Maria é muito atenciosa.');

-- Inserir Alerta/Notificação piloto para o Aluno Piloto (id 9)
INSERT INTO notifications (user_id, message, is_read) VALUES
(9, 'A sua inscrição na aula de Yoga Matinal para amanhã foi confirmada.', 0),
(9, 'O seu plano de treino expira em 10 dias. Fale com um instrutor para renovar.', 1);

-- Inserir registos de progresso para o Aluno Piloto (id 9)
INSERT INTO progress_logs (user_id, weight, body_fat, workout_notes) VALUES
(9, 78.5, 18.2, 'Treino de adaptação cumprido sem dores. Foco em melhorar cardio.'),
(9, 77.9, 17.8, 'Melhoria na resistência das passadeiras. Aumentei carga no supino livre.');
