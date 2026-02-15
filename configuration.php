<?php 
/**
 * Versão Designer Pro Elite V6 - Ultra Performance 2026
 * Foco: Estabilidade de Logo, Bypass Seguro e Cache Dinâmico
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. GESTÃO DE CACHE (Força atualização da Logo/CSS)
$assetVersion = "1.0.5"; // Mude isso quando trocar a logo para limpar o cache dos clientes
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// 2. BUSCA INTELIGENTE DE CONFIGURAÇÃO (Path Absolute)
$basePath = __DIR__ . DIRECTORY_SEPARATOR;
$configPaths = [
    $basePath . "configuration.php",
    $basePath . "includes/configuration.php",
    $basePath . "api/configuration.php"
];

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        include_once($path);
        break; 
    }
}

// 3. INTEGRAÇÃO DNS / HOST URL
if (file_exists($basePath . "includes/dns.php")) {
    include_once($basePath . "includes/dns.php");
}

// 4. TRATAMENTO DE VARIÁVEIS (Antigargalo)
$XCStreamHostUrl = $_SESSION["server"] ?? ($hostURL ?? ""); 
$XCsitetitleval  = (!empty($siteTitle)) ? $siteTitle : "WEB PLAYER";

// 5. LÓGICA DE LOGO (Prioridade: URL > Local > Placeholder)
if (!empty($logoURL)) {
    $XClogoLinkval = $logoURL;
} else {
    $XClogoLinkval = "img/logo.png"; // Padrão
    if (!file_exists($basePath . $XClogoLinkval)) {
        $XClogoLinkval = "images/logo.png";
    }
}

// Adiciona timestamp para evitar cache de imagem velha
$XClogoLinkval .= "?v=" . $assetVersion;

$XCbgLinkval        = (!empty($bgURL)) ? $bgURL : "img/background.jpg";
$XCcopyrighttextval = (!empty($copyrightText)) ? $copyrightText : "© " . date('Y') . " " . $XCsitetitleval; 

// 6. BYPASS DE LICENCIAMENTO (Local Emulation)
$XClicenseStatus    = "Active";                   
$XClocalKey         = "ELITE-BYPASS-" . md5($_SERVER['HTTP_HOST'] ?? 'localhost'); 
$XCexpireIsval      = "01/01/2099";

$checkLicense = [
    "status"    => $XClicenseStatus,
    "local_key" => $XClocalKey,
    "expire"    => $XCexpireIsval
];

// 7. CONSTANTES GLOBAIS SEGURAS
if(!defined('SITE_TITLE')) define('SITE_TITLE', $XCsitetitleval);
if(!defined('LOGO_URL'))   define('LOGO_URL', $XClogoLinkval);
if(!defined('HOST_URL'))   define('HOST_URL', $XCStreamHostUrl);
?>