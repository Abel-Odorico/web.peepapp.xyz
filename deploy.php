<?php
/**
 * ============================================
 * WEBHOOK DE AUTO DEPLOY - WEB PLAYER
 * ============================================
 * 
 * Este endpoint é chamado pelo GitHub Actions após cada push.
 * Ele executa 'git pull' no servidor para atualizar os arquivos.
 * 
 * SEGURANÇA:
 * - Requer header X-Deploy-Key com chave secreta
 * - Registra log de cada deploy
 * - Não expõe informações sensíveis
 */

// Verificar autenticação
$deployKey = $_SERVER['HTTP_X_DEPLOY_KEY'] ?? '';
$expectedKey = trim(@file_get_contents(__DIR__ . '/api/.deploy_key') ?: '');

if (empty($expectedKey) || !hash_equals($expectedKey, $deployKey)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso não autorizado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');
header('X-Robots-Tag: noindex, nofollow');

$logFile = __DIR__ . '/api/deploy.log';
$startTime = microtime(true);

try {
    $projectDir = __DIR__;

    // Executar git pull
    $output = [];
    $returnCode = 0;

    // Configurar git para confiar no diretório
    exec("cd " . escapeshellarg($projectDir) . " && git config --global --add safe.directory " . escapeshellarg($projectDir) . " 2>&1", $output, $returnCode);

    // Fazer pull
    $pullOutput = [];
    exec("cd " . escapeshellarg($projectDir) . " && git pull origin main 2>&1", $pullOutput, $returnCode);

    $pullResult = implode("\n", $pullOutput);
    $duration = round(microtime(true) - $startTime, 2);

    // Log de auditoria
    $logEntry = sprintf(
        "[%s] DEPLOY: code=%d, duration=%ss, result=%s\n",
        date('Y-m-d H:i:s'),
        $returnCode,
        $duration,
        str_replace("\n", " | ", trim($pullResult))
    );
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);

    if ($returnCode === 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Deploy executado com sucesso',
            'duration' => $duration . 's',
            'result' => $pullResult,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Git pull falhou',
            'code' => $returnCode,
            'details' => $pullResult
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno no deploy'
    ]);

    $logEntry = sprintf("[%s] ERROR: %s\n", date('Y-m-d H:i:s'), $e->getMessage());
    @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}
?>