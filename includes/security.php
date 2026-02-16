<?php
/*
 * SECURITY GATE - DNS BOUND SESSION
 * Compatível: PHP 5.6+
 * Protege: sessão, dashboard, player, ajax, M3U8
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ======================
   LOGIN EXISTE?
====================== */
if (!isset($_SESSION['webTvplayer']) || empty($_SESSION['webTvplayer'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

/* ======================
   DNS FOI AMARRADA?
====================== */
if (
    !isset($_SESSION['dns_bound']) ||
    $_SESSION['dns_bound'] !== true ||
    empty($_SESSION['dns_host'])
) {
    session_destroy();
    header("Location: index.php?blocked=dns");
    exit;
}

/* ======================
   FUNÇÕES
====================== */
require_once __DIR__ . "/functions.php";

/* ======================
   DNS AINDA É VÁLIDA?
====================== */
$sessionDns = $_SESSION['dns_host'];

if (!webtv_check_dns_allowed($sessionDns)) {
    // mata tudo se a DNS foi removida do painel
    $_SESSION = [];
    session_destroy();

    header("Location: index.php");
    exit;
}

/* ======================
   PROTEÇÃO EXTRA (OPCIONAL)
   Bind por User-Agent
====================== */
if (!isset($_SESSION['ua'])) {
    $_SESSION['ua'] = $_SERVER['HTTP_USER_AGENT'];
} else {
    if ($_SESSION['ua'] !== $_SERVER['HTTP_USER_AGENT']) {
        $_SESSION = [];
        session_destroy();
        header("Location: index.php");
        exit;
    }
}

/* ======================
   TUDO OK -> CONTINUA
====================== */
