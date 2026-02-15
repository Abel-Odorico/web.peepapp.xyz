<?php
// Mantendo toda a sua lógica PHP original intacta
ob_start();
session_start();

if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

$dynamicConfig = "includes/configuration.php";
if (file_exists($dynamicConfig)) {
    include_once($dynamicConfig);
}

$tmdbApiKey = "95497bb6942dfa426d495f9350f011b9"; 
$displayTitle = (!empty($siteTitle)) ? $siteTitle : "WEB PLAYER";
$logoSrc     = (!empty($logoURL)) ? $logoURL : "img/logo.png";
$bgFallback  = (!empty($bgURL)) ? $bgURL : "img/background.jpg"; 

function formatDisplayName($user, $serverTitle) {
    if (strpos($user, 'http') !== false || $serverTitle == 'M3U8' || empty($serverTitle)) {
        return "Acesso VIP";
    }
    return htmlspecialchars($user);
}

include "includes/header.php";
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" />
<title><?= htmlspecialchars($displayTitle) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Roboto:wght@400;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
:root {
  --netflix-red: #E50914;
  --input-bg: #161616;
  --text: #fff;
  --profile-avatar-size: 150px;
  --m3-surface: #0b0b0b;
  --m3-radius: 28px;
}

/* 120 FPS UI OPTIMIZATION */
body, html {
  margin: 0; padding: 0; height: 100%; width: 100%;
  background: #000; color: var(--text);
  font-family: 'Inter', sans-serif;
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
  touch-action: manipulation;
  scrollbar-width: none;
}

#tmdb-bg {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1;
  background-size: cover; background-position: center;
  filter: brightness(0.3);
  opacity: 0;
  transition: opacity 1.2s cubic-bezier(0.4, 0, 0.2, 1);
  will-change: opacity;
  transform: translate3d(0,0,0);
}
#tmdb-bg.loaded { opacity: 1; }

.overlay {
  position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 0;
  background: radial-gradient(circle at center, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.95) 100%);
  pointer-events: none;
}

.main-wrapper {
  display: flex; justify-content: center; align-items: center;
  min-height: 100vh; width: 100%; padding: 2em;
  position: relative; z-index: 5;
  box-sizing: border-box;
}

/* MATERIAL DESIGN 3 CARD */
.login-card {
  background: var(--m3-surface);
  padding: 3em;
  width: 100%;
  max-width: 480px;
  border-radius: var(--m3-radius);
  border: 1px solid rgba(255,255,255,0.08);
  backdrop-filter: blur(40px);
  box-shadow: 0 24px 48px rgba(0,0,0,0.6);
  will-change: transform, opacity;
}

h2 { margin-bottom: 0.5em; font-size: 2.2rem; font-weight: 800; text-align: center; color: #fff; letter-spacing: -1px; }

/* ABAS DE LOGIN HÍBRIDO */
.login-tabs {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 25px;
    border-bottom: 1px solid #333;
    padding-bottom: 10px;
}
.tab-btn {
    background: transparent;
    border: none;
    color: #666;
    font-weight: 700;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 5px 10px;
    transition: 0.3s;
    text-transform: uppercase;
}
.tab-btn.active {
    color: var(--text);
    border-bottom: 2px solid var(--netflix-red);
}
.tab-btn:hover { color: #fff; }

/* MATERIAL INPUT */
input.sel-m3u {
  width: 100%; height: 3.5em; padding: 0 1.5em; background: var(--input-bg);
  border: 1px solid #222; border-radius: 12px; outline: none; font-size: 1rem; color: #fff;
  transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1); box-sizing: border-box;
  margin-bottom: 10px;
}
input.sel-m3u:focus { border-color: var(--netflix-red); background: #1a1a1a; box-shadow: 0 0 0 4px rgba(229, 9, 20, 0.2); }

/* QUANTUM PING REAL-TIME */
.ping-realtime {
    height: 30px;
    margin: 5px 0 15px 0;
    font-size: 0.75rem;
    text-align: left;
    font-weight: 900;
    display: flex;
    align-items: center;
    gap: 10px;
    visibility: hidden;
    padding: 0 10px;
    background: rgba(255,255,255,0.03);
    border-radius: 8px;
    width: fit-content;
    will-change: contents;
}
.status-dot {
    width: 8px; height: 8px; border-radius: 50%;
    display: inline-block;
    box-shadow: 0 0 10px currentColor;
}

.recent-links {
    background: #0d0d0d; border-radius: 16px; margin-top: 8px;
    max-height: 220px; overflow-y: auto; display: none;
    border: 1px solid #222; position: absolute; width: 100%; z-index: 100;
    box-shadow: 0 10px 30px #000;
}
.recent-item {
    padding: 15px 20px; font-size: 0.9rem; border-bottom: 1px solid #1a1a1a;
    cursor: pointer; color: #888; transition: 0.2s;
}
.recent-item:hover { background: #161616; color: #fff; padding-left: 25px; }

.btn-clear-history {
    background: #000; color: #ff4d4d; padding: 12px; font-size: 0.8rem;
    text-align: center; cursor: pointer; font-weight: 800; border-top: 1px solid #222;
}

/* BTN MATERIAL */
.btn-red {
  width: 100%; height: 3.5em; background: var(--netflix-red); border: none;
  border-radius: 12px; color: #fff; font-weight: 800; font-size: 1rem;
  cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 4px 15px rgba(229, 9, 20, 0.3);
  text-transform: uppercase; letter-spacing: 1px;
}
.btn-red:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(229, 9, 20, 0.5); filter: brightness(1.1); }
.btn-red:active { transform: translateY(0); }

.profile-list { display: flex; flex-wrap: wrap; gap: 2em; justify-content: center; margin-top: 3em; }
.profile-item { cursor: pointer; width: 160px; text-align: center; }
.profile-avatar { 
    width: var(--profile-avatar-size); height: var(--profile-avatar-size); 
    border-radius: 12px; overflow: hidden; border: 4px solid transparent; 
    background: #111; margin: 0 auto 15px; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
}
.profile-item:hover .profile-avatar { border-color: #fff; transform: scale(1.05) translateY(-10px); }
.profile-avatar img { width: 100%; height: 100%; object-fit: cover; }

/* Ocultar input por padrão na troca de abas */
.input-group { display: none; }
.input-group.active-group { display: block; }

@media(max-width: 768px) {
  :root { --profile-avatar-size: 110px; }
  .login-card { padding: 2em; border-radius: 20px; }
  h2 { font-size: 1.8rem; }
}
</style>
</head>
<body>
<div id="tmdb-bg"></div>
<div class="overlay"></div>

<header style="padding: 30px 5%; position: absolute; top: 0; width: 100%; z-index: 10;">
    <img src="<?= htmlspecialchars($logoSrc) ?>" style="height: 45px; filter: drop-shadow(0 2px 10px rgba(0,0,0,0.5));">
</header>

<div class="main-wrapper">
    <div class="login-card animate__animated animate__fadeInUp" id="card-login" style="display: <?= (empty($_SESSION['webTvplayer']) || isset($_GET['new_login']) ? 'block' : 'none') ?>;">
        <h2>WEB PLAYER</h2>
        
        <div class="login-tabs">
            <button type="button" class="tab-btn active" onclick="switchTab('m3u')" id="btn-tab-m3u">LINK M3U</button>
            <button type="button" class="tab-btn" onclick="switchTab('xc')" id="btn-tab-xc">USUÁRIO E SENHA</button>
        </div>

        <form onsubmit="doLogin(event)">
            
            <div id="mode_m3u" class="input-group active-group" style="position:relative;">
                <input type="text" id="dns_input" class="sel-m3u" placeholder="URL M3U8 Completa" autocomplete="off">
                <div id="recent_box" class="recent-links"></div>
            </div>

            <div id="mode_xc" class="input-group">
                <input type="text" id="xc_dns" class="sel-m3u" placeholder="DNS / Servidor (http://...)" autocomplete="off">
                <input type="text" id="xc_user" class="sel-m3u" placeholder="Usuário" autocomplete="off">
                <input type="password" id="xc_pass" class="sel-m3u" placeholder="Senha" autocomplete="off">
            </div>
            
            <div class="ping-realtime" id="ping_info">
                <span class="status-dot" id="ping_dot"></span>
                <span id="ping_text">Sincronizando DNS...</span>
            </div>

            <button type="submit" class="btn-red btn-log">ENTRAR AGORA</button>
            
            <?php if(!empty($_SESSION['webTvplayer'])): ?>
                <button type="button" onclick="location.href='index.php'" style="width:100%; background:transparent; border:none; color:#666; margin-top:20px; cursor:pointer; font-weight:bold;">VOLTAR AO PLAYER</button>
            <?php endif; ?>
        </form>
    </div>

    <div class="profiles-wrapper animate__animated animate__fadeIn" id="card-profiles" style="display: <?= (!empty($_SESSION['webTvplayer']) && !isset($_GET['new_login']) ? 'block' : 'none') ?>; text-align:center; width:100%;">
        <h1 style="font-size: clamp(32px, 5vw, 64px); margin-bottom: 40px; font-weight: 800; letter-spacing: -2px;">Quem está assistindo?</h1>
        <div class="profile-list">
            <?php if(isset($_SESSION['webTvplayer'])): ?>
                <div class="profile-item" onclick="selectProfile()">
                    <div class="profile-avatar"><img src="https://upload.wikimedia.org/wikipedia/commons/0/0b/Netflix-avatar.png"></div>
                    <p style="font-weight:700; font-size:1.1rem; color:#aaa;"><?= formatDisplayName($_SESSION['webTvplayer']['username'], $_SESSION['webTvplayer']['server_title'] ?? '') ?></p>
                </div>
            <?php endif; ?>
            <div class="profile-item" onclick="prepareAddAccount()">
                <div class="profile-avatar" style="display:flex; align-items:center; justify-content:center; background:#1a1a1a;"><i class="fas fa-plus" style="font-size: 3rem; color: #333;"></i></div>
                <p style="font-weight:700; font-size:1.1rem; color:#444;">Adicionar Perfil</p>
            </div>
        </div>
        <div style="margin-top: 60px;">
            <button onclick="location.href='logout.php'" style="background:transparent; border:1px solid #444; color:#666; padding:15px 35px; cursor:pointer; border-radius:12px; font-weight:800; transition:0.3s;">SAIR DE TUDO</button>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let loopPing;
let currentMode = 'm3u'; // 'm3u' or 'xc'

function switchTab(mode) {
    currentMode = mode;
    $('.tab-btn').removeClass('active');
    $('.input-group').removeClass('active-group');
    
    if (mode === 'm3u') {
        $('#btn-tab-m3u').addClass('active');
        $('#mode_m3u').addClass('active-group');
        startPingLoop($('#dns_input').val());
    } else {
        $('#btn-tab-xc').addClass('active');
        $('#mode_xc').addClass('active-group');
        startPingLoop($('#xc_dns').val());
    }
}

// --- QUANTUM REAL-TIME PING: MEDE A LATÊNCIA DA PRÓPRIA DNS ---
async function runPing(rawUrl) {
    if (!rawUrl || rawUrl.length < 5) return;
    
    const info = document.getElementById('ping_info');
    const dot = document.getElementById('ping_dot');
    const text = document.getElementById('ping_text');
    
    // Extrai o host da URL colada para testar o servidor real
    let testUrl;
    try {
        let domain = rawUrl.replace('http://', '').replace('https://', '').split(/[/?#]/)[0];
        testUrl = (rawUrl.includes('https') ? 'https://' : 'http://') + domain + '/favicon.ico';
    } catch(e) { testUrl = 'https://www.google.com/favicon.ico'; }

    info.style.visibility = 'visible';
    const start = Date.now();
    
    try {
        // Fetch ultra leve com cache disabled para medir a DNS em tempo real
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 3000);

        await fetch(testUrl, { mode: 'no-cors', cache: 'no-cache', signal: controller.signal });
        const latency = Date.now() - start;
        
        let status, color;
        if (latency < 120) { status = "EXCELENTE"; color = "#00ff88"; } 
        else if (latency < 300) { status = "ESTÁVEL"; color = "#ffcc00"; } 
        else { status = "LENTA / INSTÁVEL"; color = "#ff4444"; }
        
        dot.style.backgroundColor = color;
        dot.style.color = color;
        text.innerHTML = `DNS ${status}: ${latency}ms`;
        text.style.color = color;
        
    } catch (e) {
        dot.style.backgroundColor = "#444";
        text.innerHTML = "SERVIDOR OFFLINE OU DNS INVÁLIDA";
        text.style.color = "#666";
    }
}

function startPingLoop(url) {
    clearInterval(loopPing);
    if (url && url.length > 5) {
        runPing(url);
        loopPing = setInterval(() => runPing(url), 5000);
    } else {
        document.getElementById('ping_info').style.visibility = 'hidden';
    }
}

function saveToRecent(url) {
    if(!url || url.length < 10) return;
    let recent = JSON.parse(localStorage.getItem('dns_history') || "[]");
    recent = recent.filter(item => item !== url);
    recent.unshift(url);
    if (recent.length > 5) recent.pop();
    localStorage.setItem('dns_history', JSON.stringify(recent));
}

function clearHistory() {
    if(confirm("Deseja apagar o histórico de links salvos?")) {
        localStorage.removeItem('dns_history');
        localStorage.removeItem('v90_last_m3u');
        $('#recent_box').fadeOut(100);
        document.getElementById('dns_input').value = "";
        document.getElementById('ping_info').style.visibility = 'hidden';
    }
}

function showRecent() {
    let recent = JSON.parse(localStorage.getItem('dns_history') || "[]");
    const box = document.getElementById('recent_box');
    if (recent.length > 0) {
        let itemsHtml = recent.map(url => `<div class="recent-item" onmousedown="applyDNS('${url}')">${url}</div>`).join('');
        box.innerHTML = itemsHtml + `<div class="btn-clear-history" onmousedown="clearHistory()"><i class="fas fa-trash-alt"></i> LIMPAR TUDO</div>`;
        $(box).fadeIn(200);
    }
}

function applyDNS(url) {
    switchTab('m3u');
    const input = document.getElementById('dns_input');
    input.value = url;
    $('#recent_box').fadeOut(100);
    startPingLoop(url);
}

$('#dns_input').on('focus', showRecent);
$('#dns_input').on('blur', function() {
    setTimeout(() => $('#recent_box').fadeOut(200), 300);
});

// Ping em tempo real para ambos os inputs de host
$('#dns_input, #xc_dns').on('input', function() {
    startPingLoop($(this).val());
});

async function fetchTMDB() {
    const bgElement = document.getElementById('tmdb-bg');
    try {
        const response = await fetch(`https://api.themoviedb.org/3/trending/all/week?api_key=<?= $tmdbApiKey ?>&language=pt-BR`);
        const data = await response.json();
        const item = data.results[Math.floor(Math.random() * data.results.length)];
        const imgUrl = `https://image.tmdb.org/t/p/original${item.backdrop_path}`;
        const img = new Image();
        img.src = imgUrl;
        img.onload = () => {
            bgElement.style.backgroundImage = `url(${imgUrl})`;
            bgElement.classList.add('loaded');
        };
    } catch (err) {}
}

$(function() {
    fetchTMDB();
    const saved = localStorage.getItem('v90_last_m3u');
    if (saved) {
        $("#dns_input").val(saved);
        startPingLoop(saved);
    }
});

function doLogin(e) {
    e.preventDefault();
    const btn = $(".btn-log");
    
    let finalLink = "";
    
    // Lógica Híbrida: Monta a URL baseada na aba ativa
    if (currentMode === 'xc') {
        let host = $('#xc_dns').val().trim();
        let user = $('#xc_user').val().trim();
        let pass = $('#xc_pass').val().trim();
        
        if (!host || !user || !pass) { alert("Preencha DNS, Usuário e Senha!"); return; }
        
        // Corrige protocolo se faltar
        if (!host.startsWith('http')) { host = 'http://' + host; }
        // Remove barra final se houver
        if (host.endsWith('/')) { host = host.slice(0, -1); }
        
        // Cria string compatível com a API existente
        finalLink = `${host}/get.php?username=${user}&password=${pass}&type=m3u_plus&output=ts`;
        
    } else {
        // Modo Link M3U Padrão
        finalLink = $("#dns_input").val().trim();
        if (!finalLink) { alert("Por favor, informe o link M3U!"); return; }
    }
    
    btn.prop("disabled", true).css("opacity", "0.7").html('<i class="fas fa-atom fa-spin"></i> CONECTANDO...');

    $.post("api/dns.php", { action: 'check_dns_login', dns_url: finalLink }, function(auth) {
        if (auth.status === "authorized") {
            // Salva no histórico se for link direto, ou limpa se for XC (opcional)
            if (currentMode === 'm3u') {
                saveToRecent(finalLink);
                localStorage.setItem('v90_last_m3u', finalLink);
            }

            $.post("index.php", { 
                action: 'webtvlogin', 
                uname: finalLink, 
                role: auth.level, 
                owner_id: auth.id_usuario,
                dns_host: auth.host 
            }, function(response) {
                if (response.result === "success") {
                    location.reload();
                } else { showError("Erro ao sincronizar sessão."); }
            }, "json");
        } else { showError(auth.msg || "Dados inválidos ou expirados."); }
    }, "json").fail(() => showError("Falha na conexão com o servidor."));

    function showError(msg) {
        alert(msg);
        btn.prop("disabled", false).css("opacity", "1").html('ENTRAR AGORA');
    }
}

function selectProfile() {
    const wrapper = $('#card-profiles');
    wrapper.addClass('animate__animated animate__fadeOut');
    setTimeout(() => {
        $.post("index.php", { action: 'set_profile_session' }, () => window.location.href = 'dashboard.php');
    }, 400);
}

function prepareAddAccount() {
    $('#card-profiles').fadeOut(300, () => {
        $('#card-login').fadeIn(300).addClass('animate__animated animate__fadeInUp');
    });
}
</script>
</body>
</html>