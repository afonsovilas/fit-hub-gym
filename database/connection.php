<?php

declare(strict_types=1);

/**
 * Retorna a instância PDO (Singleton).
 *
 * A instância é criada apenas uma vez por ciclo de request PHP.
 * Chamadas subsequentes a get_db() reutilizam a mesma conexão.
 *
 * @return PDO
 * @throws PDOException Se a conexão ao ficheiro SQLite falhar.
 */
function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {

        $db_path = __DIR__ . '/database.db';
        $dsn     = 'sqlite:' . $db_path;

        try {
            $pdo = new PDO($dsn);

            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

            $pdo->exec('PRAGMA foreign_keys = ON;');

            $pdo->exec('PRAGMA journal_mode = WAL;');
            $pdo->exec('PRAGMA busy_timeout = 5000;');

            // Migração automática para adicionar a coluna rejection_reason
            try {
                $pdo->exec("ALTER TABLE personal_training_bookings ADD COLUMN rejection_reason TEXT;");
            } catch (Throwable $t) {
                // Coluna já existe ou base de dados nova
            }

            // Migração automática para corrigir o CHECK constraint do status (adicionando 'rejected')
            try {
                $stmtSchema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='personal_training_bookings'");
                $schemaInfo = $stmtSchema->fetch();
                if ($stmtSchema) {
                    $stmtSchema->closeCursor();
                    $stmtSchema = null;
                }

                if ($schemaInfo && strpos($schemaInfo['sql'], "'rejected'") === false) {
                    $pdo->exec("PRAGMA foreign_keys = OFF;");
                    $pdo->beginTransaction();
                    
                    // Renomear tabela antiga
                    $pdo->exec("ALTER TABLE personal_training_bookings RENAME TO _old_personal_training_bookings;");
                    
                    // Criar tabela nova com o CHECK constraint atualizado
                    $pdo->exec("CREATE TABLE personal_training_bookings (
                        id          INTEGER PRIMARY KEY AUTOINCREMENT,
                        member_id   INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                        trainer_id  INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                        scheduled_at TEXT   NOT NULL,
                        status      TEXT    NOT NULL DEFAULT 'pending'
                                            CHECK(status IN ('pending', 'confirmed', 'cancelled', 'rejected')),
                        notes       TEXT,
                        rejection_reason TEXT,
                        created_at  TEXT    NOT NULL DEFAULT (datetime('now'))
                    );");
                    
                    // Copiar os dados existentes
                    $pdo->exec("INSERT INTO personal_training_bookings (id, member_id, trainer_id, scheduled_at, status, notes, rejection_reason, created_at)
                                SELECT id, member_id, trainer_id, scheduled_at, status, notes, rejection_reason, created_at
                                FROM _old_personal_training_bookings;");
                    
                    // Eliminar a tabela antiga
                    $pdo->exec("DROP TABLE _old_personal_training_bookings;");
                    
                    $pdo->commit();
                    $pdo->exec("PRAGMA foreign_keys = ON;");
                }
            } catch (Throwable $t) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $pdo->exec("PRAGMA foreign_keys = ON;");
                file_put_contents(__DIR__ . '/migration_error.txt', "Error: " . $t->getMessage() . "\nTrace:\n" . $t->getTraceAsString());
                error_log('[PTB CHECK MIGRATION ERROR] ' . $t->getMessage());
            }

        } catch (PDOException $e) {

            error_log('[DB ERROR] ' . $e->getMessage());

            http_response_code(500);
            die('Erro interno do servidor. Por favor, tente mais tarde.');
        }
    }

    return $pdo;
}
