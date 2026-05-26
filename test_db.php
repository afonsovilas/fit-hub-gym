<?php
/**
 * test_db.php
 *
 * Ficheiro de teste rápido para validar a conexão e a listagem de dados do FitHub no browser.
 * Aceder a este ficheiro em http://localhost:9000/test_db.php após iniciar o servidor PHP.
 *
 */

declare(strict_types=1);

require_once __DIR__ . '/src/utils.php';
require_once __DIR__ . '/database/connection.php';

try {
    // Tenta obter a instância da base de dados (PDO)
    $db = get_db();

    // 1. Obter utilizadores
    $stmtUsers = $db->query("SELECT id, name, email, role, phone, is_active FROM users ORDER BY role DESC, id ASC");
    $users = $stmtUsers->fetchAll();

    // 2. Obter aulas agendadas
    $stmtClasses = $db->query("
        SELECT c.*, u.name AS instructor_name 
        FROM classes c 
        JOIN users u ON c.instructor_id = u.id 
        ORDER BY c.scheduled_at ASC
    ");
    $classes = $stmtClasses->fetchAll();

    // 3. Obter equipamentos
    $stmtEquip = $db->query("SELECT id, name, category, status, description FROM equipment ORDER BY id ASC");
    $equipment = $stmtEquip->fetchAll();

} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - FitHub</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .test-wrapper {
            max-width: 1000px;
            margin: 40px auto;
            padding: 24px;
            font-family: system-ui, -apple-system, sans-serif;
            background: #111b27;
            color: #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.5);
            border: 1px solid #1e293b;
        }
        .status-box {
            padding: 16px;
            border-radius: 8px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .status-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .status-error {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .panel {
            background: #0f172a;
            border: 1px solid #1e293b;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .panel h2 {
            margin-top: 0;
            border-bottom: 1px solid #1e293b;
            padding-bottom: 10px;
            color: #38bdf8;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .data-table th, .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #1e293b;
        }
        .data-table th {
            color: #94a3b8;
            font-weight: 600;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-member { background: #3b82f6; color: #fff; }
        .badge-trainer { background: #10b981; color: #fff; }
        .badge-staff { background: #8b5cf6; color: #fff; }
        .badge-admin { background: #ef4444; color: #fff; }
        .badge-active { background: rgba(16, 185, 129, 0.2); color: #34d399; }
        .badge-inactive { background: rgba(239, 68, 68, 0.2); color: #f87171; }
    </style>
</head>
<body style="background: #090d16; padding: 20px;">

    <div class="test-wrapper">
        <h1 style="margin-top:0;">FitHub &mdash; Painel de Validação de Base de Dados</h1>
        <p style="color: #94a3b8; margin-bottom: 24px;">Ficheiro auxiliar para confirmar a integridade e ligação da base de dados SQLite em tempo real.</p>

        <?php if (isset($error)): ?>
            <div class="status-box status-error">
                <span>❌</span>
                <div>
                    <strong>Erro de Conexão à Base de Dados:</strong><br>
                    <?= h($error) ?>
                </div>
            </div>
        <?php else: ?>
            <div class="status-box status-success">
                <span>✔️</span>
                <div>
                    <strong>Ligação Estabelecida com Sucesso!</strong><br>
                    A carregar dados piloto do ficheiro SQLite local: <code>database/database.db</code>
                </div>
            </div>

            <!-- Secção Utilizadores -->
            <div class="panel">
                <h2>Utilizadores Pilotados (Tabela 'users')</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Papel (Role)</th>
                            <th>Telefone</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong><?= h($u['name']) ?></strong></td>
                                <td><?= h($u['email']) ?></td>
                                <td><span class="badge badge-<?= h($u['role']) ?>"><?= h($u['role']) ?></span></td>
                                <td><?= h($u['phone'] ?? 'N/D') ?></td>
                                <td>
                                    <span class="badge <?= $u['is_active'] ? 'badge-active' : 'badge-inactive' ?>">
                                        <?= $u['is_active'] ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Secção Aulas -->
            <div class="panel">
                <h2>Aulas Agendadas (Tabela 'classes' + JOIN 'users')</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Aula</th>
                            <th>Instrutor</th>
                            <th>Sala</th>
                            <th>Lotação Máxima</th>
                            <th>Data e Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($classes as $c): ?>
                            <tr>
                                <td><strong><?= h($c['name']) ?></strong></td>
                                <td><?= h($c['instructor_name']) ?></td>
                                <td><?= h($c['room']) ?></td>
                                <td><?= h($c['capacity']) ?> alunos</td>
                                <td><code><?= h($c['scheduled_at']) ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Secção Equipamento -->
            <div class="panel">
                <h2>Equipamento no Espaço Livre (Tabela 'equipment')</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Categoria</th>
                            <th>Estado</th>
                            <th>Descrição</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $eq): ?>
                            <tr>
                                <td><strong><?= h($eq['name']) ?></strong></td>
                                <td><?= h($eq['category']) ?></td>
                                <td>
                                    <span class="badge <?= $eq['status'] === 'available' ? 'badge-active' : ($eq['status'] === 'maintenance' ? 'badge-inactive' : 'badge-member') ?>">
                                        <?= h($eq['status']) ?>
                                    </span>
                                </td>
                                <td style="font-size: 0.9em; color: #94a3b8;"><?= h($eq['description'] ?? '') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </div>

</body>
</html>
