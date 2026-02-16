<?php
/**
 * PAINEL ADMIN (VERSÃO SUPREMA UNIFICADA)
 * REMOVIDO: SISTEMA DE MENSAGENS
 */
ob_start();
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// --- MANTÉM SESSÃO ATIVA ---
if (isset($_SESSION['logged_dns'])) {
    $_SESSION['last_check'] = time();
}

$configDir = __DIR__ . '/api/'; 
$passFile = $configDir . 'config.php';

// Correção de permissão e criação de diretório
if (!is_dir($configDir)) { 
    @mkdir($configDir, 0755, true); 
}

if (!file_exists($passFile)) {
    file_put_contents($passFile, "<?php \$admin_user = 'ADM'; \$admin_pass = 'ADM'; ?>");
}
include($passFile);

try {
    $dbPath = rtrim($configDir, '/') . '/.db.db'; 
    $db = new SQLite3($dbPath);
    $db->busyTimeout(5000);
    $db->exec("PRAGMA journal_mode = WAL;");

    // Tabelas Base
    $db->exec("CREATE TABLE IF NOT EXISTS dns(id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, url TEXT NOT NULL, owner_id INTEGER DEFAULT 0)");
    $db->exec("CREATE TABLE IF NOT EXISTS resellers(id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE, password TEXT, credits INTEGER DEFAULT 0, dns_limit INTEGER DEFAULT 10, role TEXT DEFAULT 'reseller', parent_id INTEGER DEFAULT 0, expiry_date TEXT, logo_url TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS online_users(session_id TEXT PRIMARY KEY, last_seen INTEGER, owner_id INTEGER, username TEXT)");
    $db->exec("CREATE TABLE IF NOT EXISTS settings(key TEXT PRIMARY KEY, value TEXT)");

} catch (Exception $e) { 
    die("Erro Crítico: Verifique as permissões da pasta 'api'."); 
}

// ============================================================
// LOGICA DERRUBAR TUDO (KICK ALL)
// ============================================================
if (isset($_GET['kick_all']) && $_GET['kick_all'] == 'true') {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        $minhaSessao = session_id();
        // Deleta todos os registros exceto o seu para evitar bug de login
        $db->exec("DELETE FROM online_users WHERE session_id != '$minhaSessao'");
        echo "<script>alert('Sucesso: Todos os usuários foram desconectados!'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        exit;
    }
}

// ============================================================
// API CAPTURA - SUPORTE PARA MASTER, REVENDA COMUM E SUB REVENDA
// ============================================================
if (isset($_POST['action']) && $_POST['action'] == 'check_dns_login') {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    
    $m3uLink = $_POST['dns_url'] ?? '';
    
    // Limpeza do Host
    $host = parse_url($m3uLink, PHP_URL_HOST) ?: $m3uLink;
    $host = trim(strtolower(preg_replace('/^https?:\/\//', '', (string)$host)), '/ ');
    $host = explode(':', $host)[0]; 

    // Busca a DNS e os dados do revendedor (incluindo parent_id para identificar Sub)
    $stmt = $db->prepare("
        SELECT d.owner_id, d.title, r.parent_id 
        FROM dns d 
        LEFT JOIN resellers r ON d.owner_id = r.id 
        WHERE d.url LIKE :host 
        LIMIT 1
    ");
    $stmt->bindValue(':host', '%' . $host . '%', SQLITE3_TEXT);
    $result = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

    if ($result) {
        $idDono = (int)$result['owner_id'];
        // Se parent_id for nulo (admin ou erro), vira -1. Se for revenda comum, geralmente é 0.
        $parentId = ($result['parent_id'] !== null) ? (int)$result['parent_id'] : -1;

        // Verifica se está PENDENTE (Admin precisa aprovar)
        if (strpos(strtoupper($result['title']), 'PENDENTE') !== false) {
            echo json_encode(['status' => 'unauthorized', 'msg' => "⚠️ Host [ $host ] aguardando liberação."]);
        } 
        else {
            // --- LÓGICA DE NÍVEIS (MASTER, SUB, COMUM) ---
            if ($idDono === 0) {
                // ID 0 é sempre o Admin
                $nivel = 'MASTER';
            } 
            else if ($parentId > 0) {
                // Se tem um Pai definido (ID > 0), é Sub Revenda
                $nivel = 'SUB REVENDA';
            } 
            else {
                // Se não é Admin e o Pai é 0 ou nulo, é Revenda Direta
                $nivel = 'REVENDA COMUM';
            }
            
            echo json_encode([
                'status' => 'authorized', 
                'level' => $nivel, 
                'id_usuario' => $idDono, 
                'host' => $host
            ]);
        }
    } else {
        // --- AUTO-CAPTURA (Insere para o usuário atual, mas como PENDENTE) ---
        if (!empty($host)) {
            $id_dono = $_SESSION['user_id'] ?? 0;
            // Insere na DNS com status Pendente. Apenas Admin aprova depois.
            $db->exec("INSERT INTO dns (title, url, owner_id) VALUES ('⚠️ PENDENTE', '$host', $id_dono)");
        }
        echo json_encode(['status' => 'unauthorized', 'msg' => "🚀 Novo host detectado! Peça para o ADM liberar o acesso."]);
    }
    exit;
}

// ============================================================
// --- LOGICA DE RECONHECIMENTO ONLINE (DO PRÓPRIO ADMIN) ---
// ============================================================
if (isset($_POST['action']) && $_POST['action'] == 'update_online_status') {
    $sess_id  = $_POST['sess_id'] ?? session_id();
    $owner_id = (int)($_POST['owner_id'] ?? 0);
    $uName    = $_POST['username'] ?? 'Admin'; // Pega o nome do Admin
    $agora    = time();

    $stmtOn = $db->prepare("INSERT OR REPLACE INTO online_users (session_id, last_seen, owner_id, username) VALUES (:sess, :seen, :owner, :user)");
    $stmtOn->bindValue(':sess', $sess_id, SQLITE3_TEXT);
    $stmtOn->bindValue(':seen', $agora, SQLITE3_INTEGER);
    $stmtOn->bindValue(':owner', $owner_id, SQLITE3_INTEGER);
    $stmtOn->bindValue(':user', $uName, SQLITE3_TEXT);
    $stmtOn->execute();

    // Limpeza automática
    $db->exec("DELETE FROM online_users WHERE last_seen < ".($agora - 120));
    exit; 
}

// --- TRAVA DE SEGURANÇA REAL-TIME ---
if (isset($_SESSION['logged_dns']) && $_SESSION['role'] !== 'admin') {
    $currentHost = $_SESSION['user_dns_host'] ?? ''; 
    if (!empty($currentHost)) {
        $stmtCheck = $db->prepare("SELECT id FROM dns WHERE url LIKE :url AND title NOT LIKE '%PENDENTE%'");
        $stmtCheck->bindValue(':url', '%' . $currentHost . '%', SQLITE3_TEXT);
        $exists = $stmtCheck->execute()->fetchArray();
        if (!$exists) {
            session_destroy();
            echo "<script>alert('Acesso interrompido: Host removido ou desativado.'); window.location.href='index.php';</script>";
            exit;
        }
    }
}

// --- LOGICA DE TROCA DE LOGO ---
if (isset($_POST['update_logo']) && isset($_SESSION['role'])) {
    $newLogo = trim($_POST['logo_url']);
    if ($_SESSION['role'] === 'admin') {
        $db->exec("INSERT OR REPLACE INTO settings (key, value) VALUES ('main_logo', '$newLogo')");
    } else {
        $stmt = $db->prepare("UPDATE resellers SET logo_url = :logo WHERE id = :id");
        $stmt->bindValue(':logo', $newLogo, SQLITE3_TEXT);
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->execute();
        $_SESSION['user_logo'] = $newLogo;
    }
    echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
}

// ============================================================
// --- LOGICA DE CRIAÇÃO DE REVENDA (COM DESCONTO, SEM LIMITE DNS) ---
// ============================================================
if (isset($_POST['add_reseller']) && isset($_SESSION['logged_dns'])) {
    // Captura dados
    $creditos_para_adicionar = (int)$_POST['res_credits'];
    $meu_id = $_SESSION['user_id'];
    $sou_admin = ($_SESSION['role'] === 'admin');

    // SE NÃO FOR ADMIN, VERIFICA E DESCONTA CRÉDITOS
    if (!$sou_admin) {
        // Pega saldo atual
        $meu_saldo = $db->querySingle("SELECT credits FROM resellers WHERE id = $meu_id");
        
        // Verifica se tem saldo suficiente
        if ($meu_saldo < $creditos_para_adicionar) {
            echo "<script>alert('ERRO: Saldo insuficiente! Você tem apenas $meu_saldo créditos.'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
            exit;
        }

        // Desconta os créditos do criador
        $db->exec("UPDATE resellers SET credits = credits - $creditos_para_adicionar WHERE id = $meu_id");
    }

    // PROSEGUE COM A CRIAÇÃO NORMAL
    $stmt = $db->prepare("INSERT OR IGNORE INTO resellers (username, password, credits, dns_limit, role, parent_id, expiry_date, logo_url) VALUES (:u, :p, :c, :l, :r, :pid, :e, :logo)");
    $stmt->bindValue(':u', trim($_POST['res_user']), SQLITE3_TEXT); 
    $stmt->bindValue(':p', trim($_POST['res_pass']), SQLITE3_TEXT);
    $stmt->bindValue(':c', $creditos_para_adicionar, SQLITE3_INTEGER); 
    // Define limite padrão (10) já que o usuário não escolhe mais
    $stmt->bindValue(':l', 10, SQLITE3_INTEGER);
    $stmt->bindValue(':r', 'reseller', SQLITE3_TEXT);
    $stmt->bindValue(':pid', $meu_id, SQLITE3_INTEGER);
    $stmt->bindValue(':e', $_POST['res_expiry'], SQLITE3_TEXT);
    $stmt->bindValue(':logo', trim($_POST['res_logo'] ?? ''), SQLITE3_TEXT);
    $stmt->execute(); 
    
    echo "<script>alert('Revenda criada com sucesso!'); window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
}

// --- LOGICA DE EXCLUSÃO E KICK (ACIONADO PELO ADMIN) ---
if (isset($_GET['kick_user'])) {
    $sessK = $db->escapeString($_GET['kick_user']);
    $origem = $_SERVER['HTTP_REFERER'] ?? '../dashboard.php';

    $dono = $db->querySingle("SELECT owner_id FROM online_users WHERE session_id = '$sessK'");

    if ($dono === 0 || $dono === '0') {
        echo "<script>alert('SEGURANÇA: Você não pode kikar o Administrador Principal!'); window.location.href='$origem';</script>";
        exit;
    }

    $db->exec("DELETE FROM online_users WHERE session_id = '$sessK'");
    echo "<script>window.location.href='$origem';</script>"; 
    exit;
}

if (isset($_GET['del_reseller']) && isset($_SESSION['logged_dns'])) {
    $idR = (int)$_GET['del_reseller'];
    $myId = $_SESSION['user_id'];
    $condicao = ($_SESSION['role'] === 'admin') ? "" : " AND parent_id = $myId";
    $db->exec("DELETE FROM resellers WHERE id = $idR $condicao");
    echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
}

// --- LOGIN SYSTEM ---
if (isset($_POST['login'])) {
    $uF = trim($_POST['user'] ?? ''); 
    $pF = trim($_POST['password'] ?? '');
    if (strtoupper($uF) === strtoupper($admin_user) && $pF === $admin_pass) {
        $_SESSION['logged_dns'] = true; $_SESSION['role'] = 'admin'; $_SESSION['user_id'] = 0; $_SESSION['user_name'] = 'MASTER'; $_SESSION['user_logo'] = '';
        echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
    } else {
        $stmt = $db->prepare("SELECT * FROM resellers WHERE username = :u AND password = :p");
        $stmt->bindValue(':u', $uF, SQLITE3_TEXT);
        $stmt->bindValue(':p', $pF, SQLITE3_TEXT);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        if ($res) {
            $_SESSION['logged_dns'] = true; $_SESSION['role'] = $res['role']; $_SESSION['user_id'] = $res['id']; 
            $_SESSION['user_name'] = $res['username']; $_SESSION['user_logo'] = $res['logo_url'];
            echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
        }
    }
}

// DADOS DO PAINEL
if (isset($_SESSION['logged_dns'])) {
    $isAdmin = ($_SESSION['role'] === 'admin');
    $myId = $_SESSION['user_id'];
    $mainLogo = $db->querySingle("SELECT value FROM settings WHERE key = 'main_logo'") ?: '';
    $displayLogo = (!empty($_SESSION['user_logo'])) ? $_SESSION['user_logo'] : $mainLogo;

    if (isset($_GET['approve']) && $isAdmin) {
        $db->exec("UPDATE dns SET title = '✅ ATIVO' WHERE id = ".(int)$_GET['approve']);
        echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
    }
    if (isset($_GET['delete'])) {
        $filter = $isAdmin ? "" : " AND owner_id = $myId";
        $db->exec("DELETE FROM dns WHERE id = ".(int)$_GET['delete'].$filter); 
        echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit;
    }

    $sqlDns = $isAdmin ? "SELECT d.*, r.username as owner_name FROM dns d LEFT JOIN resellers r ON d.owner_id = r.id ORDER BY d.id DESC" : "SELECT *, 'VOCÊ' as owner_name FROM dns WHERE owner_id = $myId ORDER BY id DESC";
    $resDns = $db->query($sqlDns);
    $dataRows = []; $totalAtivos = 0; $totalPendentes = 0;
    while($row = $resDns->fetchArray(SQLITE3_ASSOC)) { 
        $dataRows[] = $row; 
        if (strpos($row['title'], 'PENDENTE') !== false) { $totalPendentes++; } else { $totalAtivos++; }
    }
    // Para Revenda/Sub mostrar o saldo real
    if (!$isAdmin) {
        $myCredits = $db->querySingle("SELECT credits FROM resellers WHERE id = $myId") ?: 0;
    } else {
        $myCredits = "∞"; // Admin infinito
    }
    
    $totalUsers = $db->querySingle("SELECT COUNT(*) FROM resellers WHERE parent_id = $myId " . ($isAdmin ? "OR 1=1" : ""));
    $totalOnline = $db->querySingle($isAdmin ? "SELECT COUNT(*) FROM online_users" : "SELECT COUNT(*) FROM online_users WHERE owner_id = $myId");
}
if (isset($_GET['logout'])) { session_destroy(); echo "<script>window.location.href='".$_SERVER['PHP_SELF']."';</script>"; exit; }

// ============================================================
// 2. CHECK INTEGRITY (EXPULSÃO REAL)
// ============================================================
if (isset($_POST['action']) && $_POST['action'] == 'check_integrity') {
    while (ob_get_level()) { ob_end_clean(); } 
    header('Content-Type: application/json');

    $sess_id = $_POST['sess_id'] ?? session_id();
    $agora = time();

    // 1. Se for ADMIN, ignora (ele nunca é kikado)
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        echo json_encode(['status' => 'live']); 
        exit;
    }

    // 2. SÓ VERIFICA O KICK SE O USUÁRIO ESTIVER EFETIVAMENTE LOGADO
    if (!isset($_SESSION['logged_dns'])) {
        echo json_encode(['status' => 'not_logged']); 
        exit;
    }

    // 3. Verifica se o registro de "online" ainda existe no banco
    $stmtCheck = $db->prepare("SELECT count(*) FROM online_users WHERE session_id = :sess");
    $stmtCheck->bindValue(':sess', $sess_id, SQLITE3_TEXT);
    $existe = $stmtCheck->execute()->fetchArray(SQLITE3_NUM)[0];

    if ($existe > 0) {
        $db->exec("UPDATE online_users SET last_seen = $agora WHERE session_id = '$sess_id'");
        echo json_encode(['status' => 'live']);
    } else {
        session_unset();
        session_destroy();
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        echo json_encode(['status' => 'kicked']);
    }
    exit;
}

// ============================================================
// --- LOGICA DE ALTERAÇÃO DE SEGURANÇA (SENHA DO ADM) ---
// ============================================================
if (isset($_POST['change_adm_security']) && $_SESSION['role'] === 'admin') {
    $novoUser = trim($_POST['new_adm_user']);
    $novaPass = trim($_POST['new_adm_pass']);

    if (!empty($novoUser) && !empty($novaPass)) {
        $conteudo = "<?php\n";
        $conteudo .= "\$admin_user = '$novoUser';\n";
        $conteudo .= "\$admin_pass = '$novaPass';\n";
        $conteudo .= "?>";

        if (file_put_contents($passFile, $conteudo)) {
            $_SESSION['user_name'] = 'MASTER';
            echo "<script>alert('Sucesso: Login e Senha do Administrador atualizados!'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
        } else {
            echo "<script>alert('Erro: Verifique as permissões de escrita na pasta api/.');</script>";
        }
    } else {
        echo "<script>alert('Erro: Preencha todos os campos.');</script>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>PAINEL ADMIN</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');
    :root { --bg: #040508; --panel: #0c0e14; --blue: #00A8E1; --border: rgba(255,255,255,0.08); --red: #ff4757; --green: #2ed573; --gold: #ffa502; --glass: rgba(12, 14, 20, 0.9); }
    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    body { background: var(--bg); color: #fff; font-family: 'Plus Jakarta Sans', sans-serif; margin: 0; padding: env(safe-area-inset-top) 10px env(safe-area-inset-bottom) 10px; overflow-x: hidden; min-height: 100vh; }
    body::before { content: ""; position: fixed; top: -50%; left: -50%; width: 200%; height: 200%; background: radial-gradient(circle at 50% 50%, rgba(0, 168, 225, 0.08) 0%, transparent 50%); z-index: -1; }
    .login-card-box { background: var(--panel); padding: 60px 40px; border-radius: 32px; border: 1px solid var(--border); width: 100%; max-width: 450px; text-align: center; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
    .header-nav { display: flex; justify-content: space-between; align-items: center; background: var(--glass); padding: 12px 20px; border-radius: 24px; border: 1px solid var(--border); margin: 10px auto 25px; max-width: 1100px; backdrop-filter: blur(20px); position: sticky; top: 10px; z-index: 1000; }
    .btn-container { display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
    .logo-img { height: 35px; width: auto; }
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; max-width: 1100px; margin: 0 auto 25px; }
    .stat-card { background: var(--panel); border: 1px solid var(--border); padding: 20px; border-radius: 24px; text-align: center; transition: all 0.4s; }
    .btn-custom { padding: 12px 20px; border-radius: 16px; font-weight: 700; border: none; cursor: pointer; font-size: 11px; text-transform: uppercase; transition: 0.3s; color: #fff; display: inline-flex; align-items: center; gap: 8px; justify-content: center; text-decoration: none !important; }
    .btn-close-ghost { background: rgba(255, 255, 255, 0.03) !important; border: 1px solid var(--border) !important; color: rgba(255, 255, 255, 0.4) !important; width: 100%; margin-top: 15px; padding: 15px; font-size: 10px; letter-spacing: 1.5px; font-weight: 800; border-radius: 16px; cursor: pointer; transition: 0.4s; }
    .btn-close-ghost:hover { background: rgba(255, 255, 255, 0.08) !important; color: #fff !important; }
    .content-wrapper { max-width: 1100px; margin: 0 auto 30px; background: var(--panel); border-radius: 28px; border: 1px solid var(--border); }
    .table-main { width: 100%; border-collapse: collapse; }
    .table-main td { padding: 18px 25px; border-bottom: 1px solid var(--border); font-size: 14px; color: rgba(255,255,255,0.9); }
    .modal { display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0, 0, 0, 0.95); z-index: 9999; backdrop-filter: blur(15px); padding: 20px; align-items: center; justify-content: center; }
    .modal-content { background: var(--panel); padding: 30px; border-radius: 32px; width: 100%; max-width: 450px; border: 1px solid var(--border); }
    input, select, textarea { width:100%; padding:14px 18px; background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 16px; color: #fff; margin-bottom: 15px; outline: none; }
    @media (max-width: 768px) {
        .header-nav { flex-direction: column; padding: 20px; gap: 10px; }
        .btn-container { justify-content: center; width: 100%; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .table-main td { display: block; text-align: left; padding: 10px 20px; }
    }
    </style>
</head>
<body>

<?php if (!isset($_SESSION['logged_dns'])): ?>
    <div style="height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;">
        <div class="login-card-box">
            <h2 style="margin-bottom: 20px; font-weight: 800;">PAINEL <span style="color:var(--blue)">ADMIN</span></h2>
            <form method="POST">
                <input type="text" name="user" placeholder="Usuário" required>
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit" name="login" class="btn-custom" style="background:var(--blue); width:100%; padding: 18px;">ENTRAR NO PAINEL</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="header-nav">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if($displayLogo): ?><img src="<?= $displayLogo ?>" class="logo-img" alt="Logo"><?php endif; ?>
            <div style="font-size: 11px; color: #fff; opacity: 0.7;">
                Olá, <?= $isAdmin ? '<b style="color:var(--red)">MASTER</b>' : '<b style="color:var(--blue)">REVENDA</b> ('.$_SESSION['user_name'].')' ?>
            </div>
            <div style="font-size: 10px; color: var(--gold); font-weight: 800;"><i class="fa fa-coins"></i> <?= $myCredits ?></div>
        </div>
        <div class="btn-container">
            <?php if($isAdmin): ?>
                <a href="?kick_all=true" onclick="return confirm('DERRUBAR TODOS OS USUÁRIOS AGORA?')" class="btn-custom" style="background:var(--red);"><i class="fa fa-bomb"></i> DERRUBAR TUDO</a>
            <?php endif; ?>

            <a href="https://wa.me/5581991763368" target="_blank" class="btn-custom" style="background:var(--green);"><i class="fab fa-whatsapp"></i> SUPORTE</a>
            <a href="https://t.me/brtechprojetosoficial" target="_blank" class="btn-custom" style="background:var(--blue);"><i class="fab fa-telegram"></i> CANAL</a>
            
            <button onclick="document.getElementById('modalReseller').style.display='flex';" class="btn-custom" style="background:#fff; color:#000;"><i class="fa fa-user-plus"></i> NOVA REVENDA</button>
            <button onclick="document.getElementById('modalLogo').style.display='flex'" class="btn-custom" style="background:var(--blue);"><i class="fa fa-image"></i> LOGO</button>
            <?php if($isAdmin): ?>
                <button onclick="document.getElementById('modalSecurity').style.display='flex'" class="btn-custom" style="background: rgba(255,255,255,0.1);"><i class="fa-solid fa-shield-lock"></i> SEGURANÇA</button>
            <?php endif; ?>
            <a href="?logout=true" onclick="return confirm('Sair?')" class="btn-custom" style="background: var(--red);"><i class="fa fa-power-off"></i> SAIR</a>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card" onclick="document.getElementById('modalOnlineList').style.display='flex'" style="cursor:pointer;"><i class="fa fa-signal" style="color: var(--green);"></i><h2><?= $totalOnline ?></h2><p>Online</p></div>
        <div class="stat-card"><i class="fa fa-check-circle" style="color: var(--blue);"></i><h2><?= $totalAtivos ?></h2><p>Ativos</p></div>
        <div class="stat-card"><i class="fa fa-clock" style="color: var(--gold);"></i><h2><?= $totalPendentes ?></h2><p>Pendentes</p></div>
        <div class="stat-card" onclick="document.getElementById('modalManageResellers').style.display='flex'"><i class="fa fa-users"></i><h2><?= $totalUsers ?></h2><p>Revendas</p></div>
    </div>

    <div style="max-width: 1100px; margin: 0 auto 10px; padding: 0 5px;"><input type="text" id="hostSearch" placeholder="🔍 Pesquisar..."></div>

   <div class="content-wrapper">
    <table class="table-main" id="hostTable">
        <tbody>
            <?php foreach ($dataRows as $row): $isP = (strpos($row['title'], 'PENDENTE') !== false); ?>
            <tr>
                <td><span style="color: <?= $isP ? 'var(--gold)' : 'var(--green)' ?>; background: rgba(255,255,255,0.05); padding: 5px 10px; border-radius: 8px; font-weight: 800;"><?= $isP ? 'PENDENTE' : 'ATIVO' ?></span></td>
                <td style="font-family: monospace;"><?= $row['url'] ?></td>
                <?php if($isAdmin): ?><td style="opacity: 0.6; font-size: 12px;"><?= $row['owner_name'] ?: 'MASTER' ?></td><?php endif; ?>
                <td style="text-align:right;">
                    <?php if($isAdmin): ?>
                        <?php if($isP): ?><a href="?approve=<?= $row['id'] ?>"><i class="fa fa-check" style="color:var(--green); margin-right:15px;"></i></a><?php endif; ?>
                        <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Excluir?')"><i class="fa fa-trash-can" style="color:var(--red)"></i></a>
                    <?php else: ?><i class="fa fa-lock" style="opacity:0.2;"></i><?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

    <div id="modalLogo" class="modal">
        <div class="modal-content" style="border: 1px solid var(--border); background: var(--panel); border-radius: 28px; padding: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 60px; height: 60px; background: rgba(0, 168, 225, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fa fa-image" style="color: var(--blue); font-size: 24px;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800;">ALTERAR <span style="color:var(--blue)">LOGO</span></h3>
            </div>
            <form method="POST">
                <input type="hidden" name="update_logo" value="1">
                <input type="text" name="logo_url" value="<?= $displayLogo ?>" required placeholder="URL da imagem .png" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border);">
                <button type="submit" class="btn-custom" style="background: var(--blue); width: 100%; padding: 18px; border-radius: 18px;">SALVAR AGORA</button>
            </form>
            <button onclick="document.getElementById('modalLogo').style.display='none'" class="btn-close-ghost">FECHAR JANELA</button>
        </div>
    </div>

    <div id="modalReseller" class="modal">
        <div class="modal-content" style="border: 1px solid var(--border); background: var(--panel); border-radius: 28px; padding: 35px; box-shadow: 0 20px 50px rgba(0,0,0,0.6);">
            <div style="text-align: center; margin-bottom: 25px;">
                <div style="width: 60px; height: 60px; background: rgba(46, 213, 115, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fa fa-user-plus" style="color: var(--green); font-size: 24px;"></i>
                </div>
                <h3 style="margin: 0; font-size: 1.4rem; font-weight: 800;"><?= $isAdmin ? 'NOVA REVENDA' : 'SUB-REVENDA' ?></h3>
            </div>
            <form method="POST">
                <input type="hidden" name="add_reseller" value="1">
                <input type="text" name="res_user" placeholder="Usuário" required style="background: rgba(255,255,255,0.02);">
                <input type="text" name="res_pass" placeholder="Senha" required style="background: rgba(255,255,255,0.02);">
                
                <input type="number" name="res_credits" value="0" placeholder="Créditos (Desconta do seu Painel)">
                
                <input type="text" name="res_logo" placeholder="URL da Logo Individual" style="background: rgba(255,255,255,0.02);">
                <input type="date" name="res_expiry" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                <button type="submit" class="btn-custom" style="background: var(--green); width: 100%; padding: 18px; border-radius: 18px;">CRIAR AGORA</button>
            </form>
            <button onclick="document.getElementById('modalReseller').style.display='none'" class="btn-close-ghost">FECHAR JANELA</button>
        </div>
    </div>

    <div id="modalOnlineList" class="modal">
        <div class="modal-content">
            <h3 style="border-bottom:1px solid var(--border); padding-bottom:15px; margin-bottom:15px;">
                Usuários Online (<?= $totalOnline ?>)
            </h3>
            <div style="max-height:300px; overflow-y:auto;">
                <?php 
                $onQuery = $isAdmin ? "SELECT * FROM online_users ORDER BY last_seen DESC" : "SELECT * FROM online_users WHERE owner_id = $myId ORDER BY last_seen DESC"; 
                $onRes = $db->query($onQuery); 
                $found = false;
                while($o = $onRes->fetchArray(SQLITE3_ASSOC)): 
                    $found = true;
                    $isMe = ($o['session_id'] == session_id());
                    $timeAgo = time() - $o['last_seen'];
                    $statusColor = ($timeAgo < 60) ? 'var(--green)' : 'var(--gold)';
                    $showName = !empty($o['username']) ? htmlspecialchars($o['username']) : 'Visitante Sem Nome';
                ?> 
                <div style="display:flex; justify-content:space-between; align-items:center; padding:12px; border-bottom:1px solid var(--border);">
                    <div style="display:flex; flex-direction:column; gap:4px;">
                        <span style="font-weight:700; display:flex; align-items:center; gap:8px;">
                            <i class="fa fa-circle" style="font-size:8px; color:<?= $statusColor ?>"></i> 
                            <?= $showName ?>
                            <?php if($isMe) echo "<span style='font-size:9px; background:var(--blue); padding:2px 6px; border-radius:4px;'>VOCÊ</span>"; ?>
                        </span>
                        <span style="font-size:10px; opacity:0.5;">Visto há <?= $timeAgo ?>s</span>
                    </div>
                    <?php if(!$isMe): ?>
                    <a href="?kick_user=<?= urlencode($o['session_id']) ?>" 
                       onclick="return confirm('Deseja realmente derrubar a conexão de <?= $showName ?>?')" 
                       style="color:#fff; background:var(--red); padding:6px 12px; border-radius:8px; text-decoration:none; font-weight:800; font-size:10px;">
                       <i class="fa fa-power-off"></i> KICK
                    </a>
                    <?php endif; ?>
                </div> 
                <?php endwhile; ?>
                
                <?php if(!$found): ?>
                    <div style="padding:20px; text-align:center; opacity:0.5;">Ninguém online no momento.</div>
                <?php endif; ?>
            </div>
            <button onclick="document.getElementById('modalOnlineList').style.display='none'" class="btn-close-ghost">FECHAR JANELA</button>
        </div>
    </div>
    
    <div id="modalManageResellers" class="modal"><div class="modal-content"><h3>Gerenciar Revendas</h3><div style="max-height:300px; overflow-y:auto;"><?php $listRes = $isAdmin ? "SELECT * FROM resellers" : "SELECT * FROM resellers WHERE parent_id = $myId"; $resList = $db->query($listRes); while($r = $resList->fetchArray(SQLITE3_ASSOC)): ?> <div style="display:flex; justify-content:space-between; padding:10px; border-bottom:1px solid var(--border);"><b><?= $r['username'] ?></b> <a href="?del_reseller=<?= $r['id'] ?>" onclick="return confirm('Excluir?')" style="color:var(--red);"><i class="fa fa-trash"></i></a></div> <?php endwhile; ?></div><button onclick="document.getElementById('modalManageResellers').style.display='none'" class="btn-close-ghost">FECHAR JANELA</button></div></div>
    
    <div id="modalSecurity" class="modal"><div class="modal-content"><h3>Segurança Master</h3><form method="POST"><input type="hidden" name="change_adm_security" value="1"><input type="text" name="new_adm_user" value="<?= $admin_user ?>" required><input type="password" name="new_adm_pass" placeholder="Nova Senha" required><button type="submit" class="btn-custom" style="background:var(--red); width:100%;">SALVAR</button></form><button onclick="document.getElementById('modalSecurity').style.display='none'" class="btn-close-ghost">FECHAR JANELA</button></div></div>
    
    <script>
        // SCRIPT HEARTBEAT DO ADMIN (PARA APARECER NA LISTA)
        function antiKick() {
            const formData = new FormData();
            formData.append('action', 'update_online_status');
            formData.append('sess_id', '<?= session_id() ?>');
            formData.append('owner_id', '<?= $myId ?>');
            formData.append('username', '<?= $_SESSION['user_name'] ?? 'Admin' ?>'); 
            fetch('<?= $_SERVER['PHP_SELF'] ?>', { method: 'POST', body: formData });
        }
        setInterval(antiKick, 30000);
        antiKick();

        document.getElementById('hostSearch').addEventListener('keyup', function() {
            let filter = this.value.toLowerCase().trim();
            document.querySelectorAll('#hostTable tbody tr').forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
            });
        });
    </script>
<?php endif; ?>
</body>
</html>