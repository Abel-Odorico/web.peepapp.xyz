<?php
/**
 * ============================================
 * AUTO MIGRATION SYSTEM - WEB PLAYER
 * ============================================
 * 
 * Este endpoint executa migrations pendentes no banco SQLite.
 * É chamado automaticamente pelo GitHub Actions após cada deploy.
 * 
 * SEGURANÇA:
 * - Requer header X-Migration-Key com chave secreta
 * - Registra log de todas as execuções
 * - Não expõe informações sensíveis
 * 
 * COMO CRIAR UMA NOVA MIGRATION:
 * 1. Crie um arquivo em api/migrations/ com o formato:
 *    YYYY_MM_DD_HHMMSS_nome_descritivo.php
 *    Exemplo: 2026_02_15_120000_add_favorites_table.php
 * 
 * 2. O arquivo deve retornar um array com:
 *    - 'up'   => SQL para aplicar a migration
 *    - 'down' => SQL para reverter (opcional, para rollback)
 * 
 * 3. Faça push para o GitHub — a migration será executada automaticamente!
 */

// Bloqueia acesso direto via navegador sem chave
$migrationKey = $_SERVER['HTTP_X_MIGRATION_KEY'] ?? '';
$expectedKey  = trim(file_get_contents(__DIR__ . '/.migration_key') ?: '');

// Fallback: permite execução via CLI (para manutenção local)
$isCli = (php_sapi_name() === 'cli');

if (!$isCli && (empty($expectedKey) || !hash_equals($expectedKey, $migrationKey))) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'Acesso não autorizado'
    ]);
    exit;
}

// Headers de resposta
header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

// Configuração do banco
$dbPath = __DIR__ . '/api/.db.db';
$migrationsDir = __DIR__ . '/api/migrations';
$logFile = __DIR__ . '/api/migrations/migration.log';

// Garante que o diretório de migrations existe
if (!is_dir($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
}

try {
    $db = new SQLite3($dbPath);
    $db->busyTimeout(10000);
    $db->exec('PRAGMA journal_mode = WAL');
    $db->exec('PRAGMA foreign_keys = ON');
    
    // Cria tabela de controle de migrations
    $db->exec("
        CREATE TABLE IF NOT EXISTS _migrations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            migration TEXT UNIQUE NOT NULL,
            executed_at DATETIME DEFAULT (datetime('now')),
            checksum TEXT,
            status TEXT DEFAULT 'applied'
        )
    ");

    // Lista migrations já executadas
    $applied = [];
    $result = $db->query("SELECT migration FROM _migrations WHERE status = 'applied'");
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $applied[] = $row['migration'];
    }

    // Busca arquivos de migration pendentes
    $files = glob($migrationsDir . '/*.php');
    sort($files); // Ordem cronológica

    $pending = [];
    $executed = [];
    $errors = [];

    foreach ($files as $file) {
        $filename = basename($file);
        
        // Ignora o próprio sistema
        if ($filename === 'migrate.php') continue;
        
        // Se já foi aplicada, pula
        if (in_array($filename, $applied)) continue;

        $pending[] = $filename;

        try {
            // Carrega a migration
            $migration = require $file;
            
            if (!is_array($migration) || !isset($migration['up'])) {
                $errors[] = [
                    'file' => $filename,
                    'error' => 'Formato inválido: migration deve retornar array com chave "up"'
                ];
                continue;
            }

            // Executa dentro de transação
            $db->exec('BEGIN TRANSACTION');
            
            $upSql = $migration['up'];
            
            // Suporta múltiplos statements separados por ;
            if (is_string($upSql)) {
                $db->exec($upSql);
            } elseif (is_array($upSql)) {
                foreach ($upSql as $sql) {
                    $db->exec($sql);
                }
            }

            // Registra como aplicada
            $checksum = md5_file($file);
            $stmt = $db->prepare(
                "INSERT INTO _migrations (migration, checksum, status) VALUES (:name, :checksum, 'applied')"
            );
            $stmt->bindValue(':name', $filename, SQLITE3_TEXT);
            $stmt->bindValue(':checksum', $checksum, SQLITE3_TEXT);
            $stmt->execute();

            $db->exec('COMMIT');
            
            $executed[] = $filename;
            
            // Log de auditoria
            $logEntry = sprintf(
                "[%s] APPLIED: %s (checksum: %s) by %s\n",
                date('Y-m-d H:i:s'),
                $filename,
                $checksum,
                $isCli ? 'CLI' : 'GitHub Actions'
            );
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            
            $errorMsg = $e->getMessage();
            $errors[] = [
                'file' => $filename,
                'error' => $errorMsg
            ];

            // Log de erro
            $logEntry = sprintf(
                "[%s] ERROR: %s - %s\n",
                date('Y-m-d H:i:s'),
                $filename,
                $errorMsg
            );
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    $db->close();

    // Resposta
    $response = [
        'success'  => empty($errors),
        'summary'  => [
            'total_applied'   => count($applied),
            'pending_found'   => count($pending),
            'executed_now'    => count($executed),
            'errors'          => count($errors)
        ],
        'executed' => $executed,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    if (!empty($errors)) {
        $response['errors'] = $errors;
    }

    http_response_code(empty($errors) ? 200 : 207);
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erro de conexão com o banco'
    ]);
    
    // Log de erro crítico (sem expor detalhes)
    $logEntry = sprintf(
        "[%s] CRITICAL: Database connection failed - %s\n",
        date('Y-m-d H:i:s'),
        $e->getMessage()
    );
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>
