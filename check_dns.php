<?php
/**
 * VALIDADOR DE DNS - VERSÃO SILENCIOSA E BLINDADA
 */
// 1. Silencia erros para o usuário final e limpa o buffer
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

// 2. Cabeçalhos de Autorização e JSON
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf-8');

try {
    // Caminho absoluto para o banco de dados
    $dbPath = __DIR__ . '/api/.db.db';
    
    if (!file_exists($dbPath)) {
        ob_clean(); // Apaga qualquer saída anterior
        echo json_encode(['status' => 'error', 'msg' => 'Banco de dados não encontrado!']);
        exit;
    }

    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);

    // Recebe o link enviado pelo JavaScript da index
    $m3uLink = $_POST['dns_url'] ?? '';

    if (empty($m3uLink)) {
        ob_clean();
        echo json_encode(['status' => 'unauthorized', 'msg' => 'DNS vazia!']);
        exit;
    }

    // REGRA DE LIMPEZA: Extrai apenas o host (ex: servidor.com)
    $host = parse_url($m3uLink, PHP_URL_HOST) ?: $m3uLink;
    $host = preg_replace('/^https?:\/\//', '', $host);
    $host = explode(':', $host)[0];
    $host = trim(strtolower($host), '/ ');

    // Consulta se a DNS existe no banco
    $stmt = $db->prepare("SELECT COUNT(*) FROM dns WHERE url LIKE :host");
    $stmt->bindValue(':host', '%' . $host . '%', SQLITE3_TEXT);
    $count = $stmt->execute()->fetchArray()[0];

    // Limpa o buffer novamente para garantir saída 100% limpa
    ob_clean();

    if ($count > 0) {
        // DNS CADASTRADA - LIBERADO
        echo json_encode(['status' => 'authorized']);
    } else {
        // AUTO-REGISTRO: Salva a tentativa para você autorizar depois no dns.php
        $stmtInsert = $db->prepare("INSERT INTO dns (title, url) VALUES (:title, :url)");
        $stmtInsert->bindValue(':title', '⚠️ PENDENTE (Tentativa)', SQLITE3_TEXT);
        $stmtInsert->bindValue(':url', $host, SQLITE3_TEXT);
        @$stmtInsert->execute();

        echo json_encode(['status' => 'unauthorized', 'msg' => "DNS [$host] não autorizada. Registrada no painel!"]);
    }
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['status' => 'error', 'msg' => 'Falha técnica interna.']);
}
exit;