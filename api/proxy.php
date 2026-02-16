<?php
/**
 * PROXY REVERSO INTELIGENTE - VERSÃO HÍBRIDA (M3U/STREAM)
 * * Regras Aplicadas:
 * 1. Agnóstico de DNS: Funciona para qualquer URL externa passada via $_GET['url'].
 * 2. Detecção Híbrida: Analisa o Content-Type para saber se reescreve (Lista) ou streama (Vídeo).
 * 3. Bypass de CORS/Mixed Content: Permite tocar HTTP dentro de sites HTTPS.
 * 4. Otimização de Memória: Usa buffer chunks para não travar o servidor com arquivos grandes.
 */

// Configurações de Performance
set_time_limit(0);             // Sem limite de tempo para streams longos
ini_set('memory_limit', '512M'); // Memória suficiente para processar listas grandes
ini_set('max_execution_time', 0);

// =======================================================================
// 1. CABEÇALHOS CORS E SEGURANÇA
// =======================================================================
// Permite que qualquer origem (seu site) acesse este proxy
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, HEAD, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Range, Authorization");
header("Access-Control-Expose-Headers: Content-Length, Content-Range, Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { 
    http_response_code(200); 
    exit; 
}

// Validação da URL Alvo
$target_url = $_GET['url'] ?? '';

if (empty($target_url)) {
    http_response_code(400);
    die("Erro: Nenhuma URL de destino fornecida.");
}

// Tratamento básico da URL (Espaços)
$target_url = str_replace(' ', '%20', $target_url);

// Define a URL base deste proxy para usar na reescrita interna
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
$current_proxy_url = $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?url=";

// =======================================================================
// 2. FUNÇÃO CURL PADRONIZADA
// =======================================================================
function create_curl($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Ignora erros de SSL do IPTV
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0); // Infinito para stream
    
    // Simula um navegador ou Smart TV para evitar bloqueios
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    curl_setopt($ch, CURLOPT_USERAGENT, $ua);

    // Repassa Headers de Range (Vital para pular partes do vídeo)
    if (isset($_SERVER['HTTP_RANGE'])) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Range: ' . $_SERVER['HTTP_RANGE']]);
    }
    
    return $ch;
}

// =======================================================================
// 3. DETECÇÃO DE TIPO (HÍBRIDO VS STREAM)
// =======================================================================
// Faz um request HEAD rápido apenas para ler o Content-Type
$ch_head = create_curl($target_url);
curl_setopt($ch_head, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch_head, CURLOPT_HEADER, true);
curl_setopt($ch_head, CURLOPT_NOBODY, true);
$headers_raw = curl_exec($ch_head);
$content_type = curl_getinfo($ch_head, CURLINFO_CONTENT_TYPE);
$http_code = curl_getinfo($ch_head, CURLINFO_HTTP_CODE);
curl_close($ch_head);

if ($http_code >= 400) {
    http_response_code($http_code);
    die("Erro no servidor de origem: $http_code");
}

// Lógica de Detecção: É lista se tiver 'mpegurl' no tipo OU terminar com .m3u8
$is_playlist = (
    stripos($content_type, 'mpegurl') !== false || 
    stripos($content_type, 'x-mpegurl') !== false || 
    stripos($target_url, '.m3u8') !== false ||
    stripos($content_type, 'text/plain') !== false // Algumas listas vem como texto
);

// =======================================================================
// CENÁRIO A: LISTA M3U8 (REESCRITA DE URLS)
// =======================================================================
if ($is_playlist) {
    // Baixa o conteúdo da lista
    $ch = create_curl($target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $content = curl_exec($ch);
    curl_close($ch);

    if (!$content) { die("Falha ao ler a lista M3U8."); }

    // Força o header correto para o player entender
    header("Content-Type: application/vnd.apple.mpegurl");
    header("Content-Disposition: inline; filename=playlist.m3u8");

    // --- REGRAS DE REESCRITA HÍBRIDA ---

    // 1. Links Absolutos (Começam com http/https)
    // Transforma: http://server.com/video.ts  ->  proxy.php?url=http://server.com/video.ts
    $content = preg_replace_callback('/^(http.+)$/m', function($matches) use ($current_proxy_url) {
        return $current_proxy_url . urlencode(trim($matches[1]));
    }, $content);

    // 2. Chaves de Criptografia (URI="http://...")
    $content = preg_replace_callback('/(URI=")(http[^"]+)(")/', function($matches) use ($current_proxy_url) {
        return $matches[1] . $current_proxy_url . urlencode($matches[2]) . $matches[3];
    }, $content);

    // 3. Links Relativos (Não começam com # nem http) - Para listas híbridas
    // Transforma: segmento01.ts  ->  proxy.php?url=http://origem.com/segmento01.ts
    $base_url_remote = dirname($target_url) . '/';
    
    $content = preg_replace_callback('/^(?!#|http|\s)(.+)$/m', function($matches) use ($current_proxy_url, $base_url_remote) {
        $line = trim($matches[1]);
        if (empty($line)) return ""; // Ignora linhas vazias
        
        $full_url = $base_url_remote . $line;
        return $current_proxy_url . urlencode($full_url);
    }, $content);

    // Entrega a lista modificada
    echo $content;
    exit;
}

// =======================================================================
// CENÁRIO B: STREAM DIRETO (TS, MP4, MKV, IMAGEM)
// =======================================================================
else {
    $ch = create_curl($target_url);
    
    // Configurações para passar o binário direto
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    
    // Repassa headers essenciais do servidor original
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) {
        $clean = trim($header);
        $headers_allowed = [
            'Content-Type:', 
            'Content-Length:', 
            'Content-Range:', 
            'Accept-Ranges:', 
            'Last-Modified:', 
            'ETag:'
        ];
        
        foreach ($headers_allowed as $h) {
            if (stripos($clean, $h) === 0) {
                header($clean);
                break;
            }
        }
        return strlen($header);
    });

    // Escreve os dados diretamente na saída (flush) para economizar RAM
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $chunk) {
        echo $chunk;
        flush(); // Envia imediatamente para o player
        return strlen($chunk);
    });

    curl_exec($ch);
    
    if (curl_errno($ch)) {
        error_log("Erro Proxy Curl: " . curl_error($ch));
    }
    
    curl_close($ch);
    exit;
}
?>