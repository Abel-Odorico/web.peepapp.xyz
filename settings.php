<?php
/**
 * SETTINGS - NETFLIX ELITE 2026
 * - Apenas visualização de Plano e Perfil
 */
ob_start();
session_start();
error_reporting(0);

include "includes/header.php";
?>

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;700;900&display=swap');

    :root {
        --bg-netflix: #0f0f0f;
        --card-netflix: #181818;
        --red-netflix: #E50914;
        --gray-text: #808080;
        --border-netflix: #333333;
        --glass: rgba(255, 255, 255, 0.05);
    }

    body {
        background-color: var(--bg-netflix) !important;
        font-family: 'Inter', sans-serif;
        color: #fff;
        margin: 0; padding: 0;
        overflow-x: hidden;
    }

    /* --- PREMIUM NAV --- */
    .top-nav {
        position: fixed; top: 0; left: 0; width: 100%; height: 70px;
        padding: 0 4%; display: flex; align-items: center; justify-content: space-between;
        z-index: 1000; background: linear-gradient(to bottom, rgba(0,0,0,0.9) 0%, transparent 100%);
        backdrop-filter: blur(10px);
        transition: all 0.4s ease;
    }
    .top-nav.scrolled { background: #000; border-bottom: 1px solid var(--border-netflix); }
    .nav-logo { height: 28px; filter: drop-shadow(0 0 2px rgba(0,0,0,0.5)); }
    .nav-links { display: flex; gap: 25px; align-items: center; }
    .nav-link { color: #e5e5e5; text-decoration: none; font-size: 14px; font-weight: 500; transition: 0.3s; opacity: 0.8; }
    .nav-link:hover, .nav-link.active { color: #fff; opacity: 1; }

    .btn-exit-pc {
        border: 1px solid rgba(255,255,255,0.3);
        padding: 6px 18px;
        border-radius: 4px;
        font-size: 13px;
        background: transparent;
    }
    .btn-exit-pc:hover { background: var(--red-netflix); border-color: var(--red-netflix); }

    /* --- SETTINGS WRAPPER --- */
    .settings-container {
        max-width: 1050px;
        margin: 120px auto;
        padding: 40px;
        background: var(--glass);
        border: 1px solid var(--border-netflix);
        border-radius: 12px;
        animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    .settings-header {
        border-bottom: 1px solid var(--border-netflix);
        padding-bottom: 25px;
        margin-bottom: 20px;
    }
    .settings-header h1 { font-size: 38px; font-weight: 700; margin: 0; letter-spacing: -1px; }

    /* --- SECTION GRID --- */
    .setting-section {
        display: grid;
        grid-template-columns: 280px 1fr;
        padding: 35px 0;
        border-bottom: 1px solid var(--border-netflix);
        transition: 0.3s;
    }
    .setting-section:last-of-type { border-bottom: none; padding-bottom: 10px; }

    .section-title {
        color: var(--gray-text);
        text-transform: uppercase;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 1.5px;
    }

    .section-content { position: relative; }

    /* MOBILE FOOTER */
    .mobile-footer-nav {
        display: none; position: fixed; bottom: 0; width: 100%; height: 75px;
        background: rgba(10, 10, 10, 0.95); backdrop-filter: blur(20px); 
        border-top: 1px solid var(--border-netflix);
        justify-content: space-around; align-items: center; z-index: 2000;
        padding-bottom: env(safe-area-inset-bottom);
    }
    .mob-item { color: #808080; text-decoration: none; text-align: center; font-size: 10px; font-weight: 600; }
    .mob-item i { font-size: 22px; display: block; margin-bottom: 5px; }
    .mob-item.active { color: #fff; }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

    @media (max-width: 768px) {
        .top-nav { height: 65px; justify-content: center; }
        .nav-links { display: none; }
        .settings-container { margin: 80px 15px 90px; padding: 20px; border: none; background: transparent; }
        .setting-section { grid-template-columns: 1fr; padding: 15px 0; border-bottom: 1px solid #222; }
        .mobile-footer-nav { display: flex; }
        .section-title { font-size: 12px; color: var(--red-netflix); margin-bottom: 5px; }
        .settings-header h1 { font-size: 26px; }
    }
</style>

<div class="top-nav" id="topNav">
    <a href="dashboard.php"><img src="img/logo.png" class="nav-logo" alt="Netflix"></a>
    <div class="nav-links">
        <a href="dashboard.php" class="nav-link">Início</a>
        <a href="live.php" class="nav-link">Canais</a>
        <a href="series.php" class="nav-link">Séries</a>
        <a href="movies.php" class="nav-link">Filmes</a>
        <a href="settings.php" class="nav-link active">Minha Conta</a>
        <a href="index.php" class="nav-link btn-exit-pc"><i class="fa fa-sign-out-alt"></i> Sair</a>
    </div>
</div>

<div class="settings-container">
    <div class="settings-header">
        <h1>Conta</h1>
    </div>

    <div class="setting-section">
        <div class="section-title">Plano e Perfil</div>
        <div class="section-content">
            <div style="display:flex; align-items:center; gap:15px;">
                <div style="width:45px; height:45px; background:var(--red-netflix); border-radius:4px; display:flex; align-items:center; justify-content:center; font-weight:900; font-size:18px;">
                    <?= strtoupper(substr($_SESSION["webTvplayer"]["username"], 0, 1)) ?>
                </div>
                <div>
                    <p style="margin:0; font-weight:700; font-size:16px;"><?= htmlspecialchars($_SESSION["webTvplayer"]["username"]) ?></p>
                    <p style="margin:2px 0; color:#46d369; font-size:12px; font-weight:700;">PREMIUM 4K</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="mobile-footer-nav">
    <a href="dashboard.php" class="mob-item"><i class="fa fa-home"></i>Início</a>
    <a href="live.php" class="mob-item"><i class="fa fa-tv"></i>Canais</a>
    <a href="movies.php" class="mob-item"><i class="fa fa-film"></i>Filmes</a>
    <a href="series.php" class="mob-item"><i class="fa fa-clone"></i>Séries</a>
    <a href="settings.php" class="mob-item active"><i class="fa fa-user-circle"></i>Conta</a>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    // KEEP ALIVE
    setInterval(function() { $.post("includes/ajax-control.php", { action: 'ping' }); }, 120000);

    // Efeito Scroll
    $(window).scroll(function() {
        if ($(window).scrollTop() > 50) { $('#topNav').addClass('scrolled'); } 
        else { $('#topNav').removeClass('scrolled'); }
    });
});
</script>

<?php ob_end_flush(); ?>