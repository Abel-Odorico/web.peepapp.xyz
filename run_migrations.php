<?php
// run_migrations.php
// Script para executar migrações de banco de dados SQLite automaticamente via GitHub Actions

// 1. Configurações de Segurança
define('MIGRATION_TOKEN', 'PeepAppSecretMigrationToken2026'); // ALTERE ISTO PARA UM TOKEN SEGURO E ADICIONE AO GITHUB SECRETS
define('DB_PATH', __DIR__ . '/api/.db.db');
define('MIGRATIONS_DIR', __DIR__ . '/migrations/');

// 2. Verificação de Token de Segurança
$requestToken = $_GET['token'] ?? '';
if ($requestToken !== MIGRATION_TOKEN) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Acesso negado: Token inválido.']));
}

// 3. Verifica existência do banco e pasta de migrações
if (!file_exists(DB_PATH)) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Banco de dados não encontrado em ' . DB_PATH]));
}

if (!is_dir(MIGRATIONS_DIR)) {
    mkdir(MIGRATIONS_DIR, 0755, true);
}

// 4. Conecta ao SQLite
try {
    $db = new SQLite3(DB_PATH);
    $db->busyTimeout(5000);
    $db->enableExceptions(true);
} catch (Exception $e) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => 'Erro ao conectar ao banco: ' . $e->getMessage()]));
}

// 5. Cria tabela de controle de migrações se não existir
$db->exec("CREATE TABLE IF NOT EXISTS migrations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    migration TEXT NOT NULL UNIQUE,
    executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

// 6. Lista arquivos de migração (.sql)
$migrationFiles = glob(MIGRATIONS_DIR . '*.sql');
sort($migrationFiles); // Garante ordem alfabética (001_..., 002_...)

$executedMigrations = 0;
$results = [];

// 7. Loop de Execução
foreach ($migrationFiles as $file) {
    $filename = basename($file);

    // Verifica se já foi executada
    $stmt = $db->prepare("SELECT COUNT(*) FROM migrations WHERE migration = :migration");
    $stmt->bindValue(':migration', $filename, SQLITE3_TEXT);
    $count = $stmt->execute()->fetchArray()[0];

    if ($count == 0) {
        //Executa a migração
        $sql = file_get_contents($file);
        
        try {
            // Inicia transação para cada arquivo
            $db->exec('BEGIN');
            
            // SQLite exec() suporta múltiplos comandos separados por ; na maioria das versões recentes,
            // mas é mais seguro dividir se houver problemas. Por enquanto, assumimos suporte para simplificar script único.
            // Para separar:
            // $commands = explode(';', $sql); foreach($commands as $cmd) { if(trim($cmd)) $db->exec($cmd); }
            // Mas exec() direto é atômico se falhar.
            
            $db->exec($sql);
            
            // Registra sucesso
            $stmtInsert = $db->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
            $stmtInsert->bindValue(':migration', $filename, SQLITE3_TEXT);
            $stmtInsert->execute();
            
            $db->exec('COMMIT');
            
            $results[] = ["file" => $filename, "status" => "success"];
            $executedMigrations++;
            
        } catch (Exception $e) {
            $db->exec('ROLLBACK');
            $results[] = ["file" => $filename, "status" => "error", "message" => $e->getMessage()];
            
            // Para execução em erro crítico se desejar, ou continua. 
            // Geralmente migrações devem parar no primeiro erro.
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => "Erro na migração $filename: " . $e->getMessage(), 'details' => $results]);
            exit;
        }
    }
}

echo json_encode([
    'status' => 'success', 
    'message' => "$executedMigrations migrações executadas.",
    'details' => $results
]);
?>
