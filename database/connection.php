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

        } catch (PDOException $e) {

            error_log('[DB ERROR] ' . $e->getMessage());

            http_response_code(500);
            die('Erro interno do servidor. Por favor, tente mais tarde.');
        }
    }

    return $pdo;
}
