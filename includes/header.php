<?php
/*
 * Versão otimizada do seu script PHP para Web Player
 * Compatível com PHP 5.6
 */

session_start();
include_once "includes/functions.php";

// Variáveis globais
$checkLicense = "";
$bar = "/";

// Garantir que as variáveis de configuração estejam definidas
$XCStreamHostUrl = isset($XCStreamHostUrl) ? $XCStreamHostUrl : "";
$XClicenseIsval = isset($XClicenseIsval) ? $XClicenseIsval : "";
$XClocalKey = isset($XClocalKey) ? $XClocalKey : "";
$SessioStoredUsername = !empty($_SESSION["webTvplayer"]["username"]) ? $_SESSION["webTvplayer"]["username"] : "";

// Ajusta a barra se a URL terminar com "/"
if (substr($XCStreamHostUrl, -1) == "/") {
    $bar = "";
}

// Verifica o arquivo de configuração
if (isset($configFileCheck) && $configFileCheck["result"] == "success") {
    require "configuration.php";
} else {
    // Cria arquivo vazio se não existir
    if (!file_exists("configuration.php")) {
        file_put_contents("configuration.php", "<?php\n// Configuração padrão\n");
    }
}

/**
 * CONTROLE DE ACESSO - ELITE V7 PRO
 * Define quais páginas podem ser acessadas sem sessão ativa
 */

// Adicione TODAS as suas páginas de gerenciamento aqui
$excecoes = [
    "index", 
    "dns", 
    "dns_create", 
    "dns_update", 
    "dns_edit" // Corrigido: Adicionada vírgula e nome do arquivo
]; 

// Verifica se a sessão existe ou se a página atual é uma exceção
if (empty($_SESSION["webTvplayer"]) && !in_array($activePage, $excecoes)) {
    header("Location: index.php");
    exit;
}

// Variáveis de sessão
if (isset($_SESSION["webTvplayer"])) {
    $username = $_SESSION["webTvplayer"]["username"];
    $password = $_SESSION["webTvplayer"]["password"];
    $hostURL  = $XCStreamHostUrl;
}

// Configurações do usuário via cookie
$ShiftedTimeEPG = 0;
$headerparentcondition = "";
$GlobalTimeFormat = "12";

if (isset($_COOKIE["settings_array"])) {
    $SettingArray = json_decode($_COOKIE["settings_array"]);
    if (isset($SettingArray->{$SessioStoredUsername}) && !empty($SettingArray->{$SessioStoredUsername})) {
        $userSettings = $SettingArray->{$SessioStoredUsername};
        $ShiftedTimeEPG = isset($userSettings->epgtimeshift) ? $userSettings->epgtimeshift : 0;
        $GlobalTimeFormat = isset($userSettings->timeformat) ? $userSettings->timeformat : "12";
        $headerparentcondition = isset($userSettings->parentpassword) ? $userSettings->parentpassword : "";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?= isset($XCsitetitleval) ? htmlspecialchars($XCsitetitleval) : "" ?></title>

<!-- Estilos CSS -->
<style>
:root {
  --primary-color: #fff;
  --dark-gray: #222;
  --almost-black: #111;
  --semi-white: #ccc;
  --blue: #3498db;
  --red: #e74c3c;
  --standard: 1.25rem;
  --big: 2rem;
  --small: 0.7rem;
  --serif: Georgia, serif;
}
body {
  background: black;
  margin: 0;
  font-family: var(--serif);
}
#cbp-spmenu-s1 {
  padding-bottom: 80px;
}
</style>

<!-- Arquivos de CSS -->
<link href="css/bootstrap.css" rel="stylesheet" />
<link href="css/style.css" rel="stylesheet" />
<link href="css/owl.carousel.css" rel="stylesheet" />
<link href="css/font-awesome.min.css" rel="stylesheet" />
<link href="css/scrollbar.css" rel="stylesheet" />
<link rel="stylesheet" href="css/rippler.css" />

<!-- jQuery -->
<script src="js/jquery-1.11.3.min.js"></script>

<!-- Compatibilidade IE8 -->
<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
</head>
<body>
<div class="body-content">
  <div class="overlay"></div>
</body>
</html>