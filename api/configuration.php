<?php 
/**
 * Versão Designer Pro Elite V6 - Total Bypass & Performance
 * Sincronização Dinâmica Corrigida - 2026
 */

if (!isset($_SESSION)) {
    session_start();
}

// 1. LIMPEZA DE CACHE (Essencial para troca de logo em tempo real)
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 2. BUSCA DO ARQUIVO DE CONFIGURAÇÃO (LOGO MANAGER)
// Definimos a prioridade de busca. O arquivo na raiz sempre manda.
$configFound = false;
$paths = [
    "configuration.php",
    "includes/configuration.php",
    "api/configuration.php"
];

foreach ($paths as $path) {
    if (file_exists($path)) {
        include_once($path);
        $configFound = true;
        break; 
    }
}

// 3. INTEGRAÇÃO COM DNS (URL DO SERVIDOR)
if (file_exists("includes/dns.php")) {
    include_once("includes/dns.php");
} elseif (file_exists("../includes/dns.php")) {
    include_once("../includes/dns.php");
}

// 4. CONFIGURAÇÕES DE CONEXÃO
$XCStreamHostUrl = $_SESSION["server"] ?? (isset($hostURL) ? $hostURL : ""); 

// 5. IDENTIDADE VISUAL DINÂMICA
// Se o Manager salvou as variáveis no configuration.php, usamos elas.
$XCsitetitleval     = (isset($siteTitle) && !empty($siteTitle)) ? $siteTitle : "NETFLIX";

// Tratamento de Logo com Fallback de diretório
if (isset($logoURL) && !empty($logoURL)) {
    $XClogoLinkval = $logoURL;
} else {
    // Busca física da logo caso o arquivo de config falhe
    $XClogoLinkval = file_exists("img/logo.png") ? "img/logo.png" : "images/logo.png";
}

$XCbgLinkval        = (isset($bgURL) && !empty($bgURL)) ? $bgURL : "img/background.jpg";
$XCcopyrighttextval = (isset($copyrightText) && !empty($copyrightText)) ? $copyrightText : "© " . date('Y') . " " . $XCsitetitleval; 

// 6. BYPASS DE LICENCIAMENTO ELITE (NULLED)
$XClicenseStatus    = "Active";                  
$XClocalKey         = "LOCAL-BYPASS-" . md5($_SERVER['HTTP_HOST'] ?? 'localhost'); 
$XCexpireIsval      = "01/01/2099";
$XCcheckUpdate      = false; 

$checkLicense = array(
    "status"    => $XClicenseStatus,
    "local_key" => $XClocalKey,
    "expire"    => $XCexpireIsval
);

// 7. DEFINIÇÃO DE CONSTANTES GLOBAIS
if(!defined('SITE_TITLE')) define('SITE_TITLE', $XCsitetitleval);
if(!defined('LOGO_URL')) define('LOGO_URL', $XClogoLinkval);
?>