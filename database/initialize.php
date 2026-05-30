<?php

declare(strict_types=1);

require_once __DIR__ . '/connection.php';

try {
    echo "=== Inicialização da Base de Dados do FitHub ===" . PHP_EOL;

    $db_file = __DIR__ . '/database.db';
    $schema_file = __DIR__ . '/schema.sql';

    if (!file_exists($schema_file)) {
        throw new Exception("Ficheiro schema.sql não encontrado em: $schema_file");
    }

    echo "[1/3] A estabelecer ligação ao SQLite... ";
    $db = get_db();
    echo "OK! (Ficheiro: $db_file)" . PHP_EOL;

    echo "[2/3] A ler o esquema de tabelas e dados piloto de schema.sql... ";
    $sql = file_get_contents($schema_file);
    if ($sql === false || trim($sql) === '') {
        throw new Exception("Erro ao ler ou ficheiro schema.sql vazio.");
    }
    echo "OK!" . PHP_EOL;

    echo "[3/3] A executar queries DDL e DML (seeding)... " . PHP_EOL;
    
    $db->exec($sql);
    
    echo "OK! Base de dados inicializada com sucesso com dados piloto!" . PHP_EOL;
    echo "=================================================" . PHP_EOL;
    echo "Utilizadores de teste prontos (Password comum: '123456'):" . PHP_EOL;
    echo " - Admin: admin@fithub.pt" . PHP_EOL;
    echo " - Membro: aluno@fithub.pt" . PHP_EOL;
    echo " - Treinador: joao@fithub.pt" . PHP_EOL;
    echo "=================================================" . PHP_EOL;

} catch (Throwable $e) {
    fwrite(STDERR, "ERRO CRÍTICO NA INICIALIZAÇÃO:" . PHP_EOL);
    fwrite(STDERR, $e->getMessage() . PHP_EOL);
    exit(1);
}
