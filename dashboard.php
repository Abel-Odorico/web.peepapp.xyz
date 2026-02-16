<?php
/**
 * DASHBOARD PREMIUM V92 - ORIGINAL CENTERED
 * Design Original Preservado + Fix Centralização e Compactação (TMDB Polished)
 * FEATURE: Gerenciador de Acessos + ANTI-KICK SYSTEM
 */
ob_start();
session_start();
error_reporting(0);
if (!isset($_SESSION["webTvplayer"]) || empty($_SESSION["webTvplayer"])) {
    header("Location: index.php"); 
    exit;
}

$activePage = "dashboard";
$username = $_SESSION['webTvplayer']['username'] ?? 'Assinante';

$acessosConectados = $_SESSION['multi_acessos'] ?? [
    ['user' => $username, 'active' => true, 'avatar' => 'https://upload.wikimedia.org/wikipedia/commons/0/0b/Netflix-avatar.png'],
];

include "includes/header.php"; 
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;900&display=swap');

    :root {
        --bg-main: #0a0a0a;
        --netflix-red: #E50914;
        --text-color: #fff;
        --glass: rgba(255, 255, 255, 0.1);
        --glass-border: rgba(255, 255, 255, 0.15);
        --nav-height: 75px;
    }

   body, html {
    background-color: var(--bg-main);
    font-family: 'Inter', sans-serif;
    color: var(--text-color);
    margin: 0; padding: 0;
    width: 100%; 
    height: 100%;
    min-height: 100dvh; 
    overflow-x: hidden;
    position: relative;
}

    .top-nav {
        position: fixed; top: 0; left: 0; width: 100%; height: 70px;
        padding: 0 4%; display: flex; align-items: center; justify-content: space-between;
        z-index: 1000;
        background: linear-gradient(to bottom, rgba(0,0,0,0.8) 0%, transparent 100%);
        transition: all 0.4s ease;
    }
    .top-nav.scrolled {
        background: rgba(10, 10, 10, 0.95);
        height: 60px;
        border-bottom: 1px solid var(--glass-border);
    }

    .nav-logo { height: 28px; width: auto; filter: drop-shadow(0 0 5px rgba(229, 9, 20, 0.2)); }
    .nav-links { display: flex; gap: 25px; align-items: center; }
    .nav-link { color: #e5e5e5; text-decoration: none; font-size: 14px; transition: 0.3s; opacity: 0.8; }
    .nav-link:hover, .nav-link.active { color: #fff; opacity: 1; font-weight: 600; }

    .profile-section {
        position: relative; 
        display: flex; 
        align-items: center; 
        gap: 10px;
        z-index: 2100; 
        cursor: pointer; 
        padding: 6px 12px;
        background: var(--glass); 
        border: 1px solid var(--glass-border);
        border-radius: 40px; 
        transition: 0.3s;
        margin-right: 80px; 
    }
    .avatar-box { width: 28px; height: 28px; border-radius: 50%; overflow: hidden; }
    .avatar-box img { width: 100%; height: 100%; object-fit: cover; }

    .dropdown-menu {
        position: absolute; top: 50px; right: 0; 
        background: #111;
        border: 1px solid var(--glass-border); min-width: 240px;
        display: none; flex-direction: column; padding: 10px 0; border-radius: 12px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.8);
    }
    .profile-section.active .dropdown-menu { display: flex; }
    .dropdown-header { padding: 10px 20px; font-size: 11px; text-transform: uppercase; color: #666; font-weight: 800; }
    .access-item { display: flex; align-items: center; gap: 12px; padding: 10px 20px; transition: 0.2s; border-left: 3px solid transparent; }
    .access-item:hover { background: rgba(255,255,255,0.05); }
    .access-item.active { border-left-color: var(--netflix-red); background: rgba(229, 9, 20, 0.05); }
    .access-avatar { width: 24px; height: 24px; border-radius: 4px; }
    .access-name { font-size: 13px; color: #fff; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dropdown-divider { height: 1px; background: var(--glass-border); margin: 8px 0; }
    .dropdown-item { padding: 12px 20px; color: #fff; text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 12px; transition: 0.2s; }
    .dropdown-item i { width: 16px; text-align: center; color: #888; }
    .dropdown-item:hover { color: var(--netflix-red); }

    /* --- NAVBAR MOBILE SÓLIDA --- */
    .mobile-nav {
        display: none;
        position: fixed;
        bottom: 15px; left: 50%;
        transform: translateX(-50%);
        width: 90%; max-width: 480px;
        height: 65px;
        background: #141414; /* Fundo sólido */
        border-radius: 15px;
        border: 1px solid #333;
        z-index: 5000;
        box-shadow: 0 8px 25px rgba(0,0,0,0.9);
    }

    .mobile-nav-items { display: flex; justify-content: space-around; align-items: center; height: 100%; }
    .mob-item { color: #888; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 10px; gap: 4px; flex: 1; }
    .mob-item i { font-size: 20px; }
    .mob-item.active { color: #fff; font-weight: 700; }
    .mob-item.exit { color: var(--netflix-red); }

    /* --- BANNER RESPONSIVO E CENTRALIZADO (MODIFICADO AQUI) --- */
    .hero-wrapper {
        position: relative; width: 100%; height: 100vh; height: 100dvh; 
        display: flex; align-items: center; justify-content: center; /* Centralização Absoluta */
        background-color: #000; overflow: hidden;
    }
     
    .hero-content { 
        position: relative; z-index: 10; padding: 0 5%; width: 100%; 
        max-width: 800px; /* Mais compacto */
        opacity: 0; transform: translateY(30px); transition: all 1s ease-out;
        
        /* Centralização Visual */
        text-align: center; 
        display: flex; flex-direction: column; align-items: center; justify-content: center;
    }

    .meta-row, .actions-row { 
        display: flex; justify-content: center; align-items: center; 
    }

    .hero-bg {
        position: absolute; inset: 0; background-size: cover; background-position: center center;
        z-index: 0; opacity: 0; transform: scale(1.1);
        transition: opacity 1.5s ease, transform 6s cubic-bezier(0.25, 0.1, 0.25, 1);
        will-change: opacity, transform;
    }
    .hero-bg.active { opacity: 0.7; transform: scale(1); } /* Opacidade ajustada para leitura */

    /* Gradiente mais suave para destacar o centro */
    .hero-overlay {
        position: absolute; inset: 0; z-index: 1;
        background: radial-gradient(circle at center, transparent 0%, #0a0a0a 120%),
                    linear-gradient(to top, #0a0a0a 5%, transparent 60%);
    }

    .hero-content.active { opacity: 1; transform: translateY(0); }
    
    /* Títulos Responsivos e Compactos */
    .fallback-title { 
        font-size: clamp(2rem, 5vw, 4rem); 
        font-weight: 900; 
        margin-bottom: 10px; 
        line-height: 1.1; 
        text-shadow: 0 4px 30px rgba(0,0,0,0.8);
        text-align: center;
        letter-spacing: -1px;
    }
    
    .movie-desc { 
        font-size: 1rem; 
        line-height: 1.6; 
        color: #e0e0e0; 
        margin-bottom: 25px; 
        max-width: 650px; 
        text-shadow: 0 2px 10px rgba(0,0,0,0.8);
        text-align: center;
        display: -webkit-box;
        -webkit-line-clamp: 3; /* Limita texto */
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 10px; padding: 12px 30px; border-radius: 50px; font-weight: 700; font-size: 0.95rem; text-decoration: none; border: none; cursor: pointer; transition: 0.3s; }
    .btn-play { background: #fff; color: #000; box-shadow: 0 0 20px rgba(255,255,255,0.2); }
    .btn-play:hover { transform: scale(1.05); }
    .btn-info { background: rgba(100, 100, 100, 0.5); color: #fff; backdrop-filter: blur(5px); }

    /* --- MODAL DO TRAILER --- */
    .modal-trailer { display: none; position: fixed; inset: 0; z-index: 9999; background: rgba(0,0,0,0.98); align-items: center; justify-content: center; }
    .modal-content { position: relative; width: 95%; max-width: 900px; background: #000; border-radius: 12px; }
    .video-container { position: relative; padding-bottom: 56.25%; height: 0; border-radius: 12px; overflow: hidden; }
    .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
    .close-modal { position: absolute; top: -50px; right: 0; color: #fff; background: #222; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 50%; cursor: pointer; }

/* --- AJUSTES FINAIS MOBILE --- */
@media (max-width: 768px) {
    .mobile-nav { display: block; }
    body { padding-bottom: 90px !important; }
    .top-nav { display: none !important; }
    
    .hero-content { 
        text-align: center; 
        padding: 0 20px; 
        align-items: center;
    }
    .fallback-title { 
        font-size: 2.2rem; 
    }
    .movie-desc {
        font-size: 0.9rem; 
        -webkit-line-clamp: 3; 
        margin-bottom: 20px;
    }
    .btn-action {
        padding: 10px 24px; 
        font-size: 0.85rem;
    }
}
</style>

<nav class="top-nav" id="mainNav">
    <div class="nav-left-group">
        <a href="dashboard.php"><img src="img/logo.png" class="nav-logo" alt="Logo"></a>
        <div class="nav-links">
            <a href="dashboard.php" class="nav-link active">Início</a>
            <a href="live.php" class="nav-link">Canais</a>
            <a href="movies.php" class="nav-link">Filmes</a>
            <a href="series.php" class="nav-link">Séries</a>
        </div>
    </div>
    <div class="profile-section" id="profileToggle">
        <div class="avatar-box"><img src="https://upload.wikimedia.org/wikipedia/commons/0/0b/Netflix-avatar.png"></div>
        <span style="font-size: 13px; font-weight: 600;"><?= htmlspecialchars($username) ?></span>
        <div class="dropdown-menu">
            <div class="dropdown-header">Perfis Conectados</div>
            <?php foreach($acessosConectados as $acesso): ?>
            <div class="access-item <?= $acesso['active'] ? 'active' : '' ?>">
                <img src="<?= $acesso['avatar'] ?>" class="access-avatar">
                <span class="access-name"><?= htmlspecialchars($acesso['user']) ?></span>
                <?php if($acesso['active']): ?><i class="fa fa-circle" style="font-size: 6px; color: #46d369;"></i><?php endif; ?>
            </div>
            <?php endforeach; ?>
            <div class="dropdown-divider"></div>
            <a href="index.php?select_profile=true" class="dropdown-item"><i class="fa fa-users"></i> Gerenciar Perfis</a>
            <a href="index.php?new_login=true" class="dropdown-item"><i class="fa fa-plus"></i> Adicionar Conta</a>
            <a href="settings.php" class="dropdown-item"><i class="fa fa-cog"></i> Configurações</a>
            <div class="dropdown-divider"></div>
            <a href="logout.php" class="dropdown-item" style="color:#ff4b4b;"><i class="fa fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
</nav>

<nav class="mobile-nav">
    <div class="mobile-nav-items">
        <a href="live.php" class="mob-item"><i class="fa-solid fa-tv"></i><span>Canais</span></a>
        <a href="movies.php" class="mob-item"><i class="fa-solid fa-film"></i><span>Filmes</span></a>
        <a href="index.php?select_profile=true" class="mob-item"><i class="fa-solid fa-user-circle"></i><span>Contas</span></a>
        <a href="settings.php" class="mob-item"><i class="fa fa-rss"></i><span>Config</span></a>
        <a href="series.php" class="mob-item"><i class="fa-solid fa-layer-group"></i><span>Séries</span></a>
        <a href="logout.php" class="mob-item exit"><i class="fa-solid fa-power-off"></i><span>Sair</span></a>
    </div>
</nav>

<section class="hero-wrapper">
    <div class="hero-bg" id="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content" id="hero-content">
        <h1 id="fallback-title" class="fallback-title"></h1>
        <div class="meta-row" style="margin-bottom: 15px; gap: 12px;">
            <span id="match-score" style="color:#46d369; font-weight:800; font-size: 0.85rem;"></span>
            <span id="meta-year" style="font-weight:700; background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;"></span>
            <span id="media-badge" style="border: 1px solid rgba(255,255,255,0.4); padding: 1px 6px; font-size: 0.65rem; border-radius: 2px; font-weight: 800;"></span>
        </div>
        <p class="movie-desc" id="movie-desc"></p>
        <div class="actions-row" style="gap:12px;">
            <a id="btn-play-link" href="#" class="btn-action btn-play"><i class="fa fa-play"></i> Assistir</a>
            <button class="btn-action btn-info" onclick="openTrailer()"><i class="fa fa-play-circle"></i> Trailer</button>
        </div>
    </div>
</section>

<div id="trailerModal" class="modal-trailer">
    <div class="modal-content">
        <div class="close-modal" onclick="closeTrailer()"><i class="fa fa-times"></i></div>
        <div class="video-container">
            <iframe id="trailerPlayer" src="" allow="autoplay; encrypted-media; fullscreen" allowfullscreen frameborder="0"></iframe>
        </div>
    </div>
</div>

<script>
const apiKey = '95497bb6942dfa426d495f9350f011b9';
let currentTrailerKey = '';
let trendingList = [];
let currentIndex = 0;
let bannerTimer;
const isMobile = window.innerWidth <= 768;

const detailsCache = new Map();

async function initHero() {
    const lang = "pt-BR";
    const urls = [
        `https://api.themoviedb.org/3/discover/movie?api_key=${apiKey}&language=${lang}&sort_by=popularity.desc&include_adult=false`, 
        `https://api.themoviedb.org/3/discover/tv?api_key=${apiKey}&language=${lang}&sort_by=popularity.desc&include_adult=false`
    ];

    try {
        const responses = await Promise.all(urls.map(url => fetch(url).then(r => r.json())));
        let combined = [];
        
        responses.forEach(data => {
            if (data.results) {
                combined = [...combined, ...data.results.map(item => ({
                    ...item,
                    media_type: item.title ? 'movie' : 'tv'
                }))];
            }
        });

        trendingList = combined
            .filter(i => i.backdrop_path && i.overview)
            .filter((v, i, a) => a.findIndex(t => (t.id === v.id)) === i)
            .sort(() => Math.random() - 0.5) 
            .slice(0, 10);

        if(trendingList.length > 0) {
            window.requestAnimationFrame(startRotation);
        }
    } catch (e) { console.error(e); }
}

function startRotation() {
    updateBanner();
    if(bannerTimer) clearInterval(bannerTimer);
    bannerTimer = setInterval(() => {
        currentIndex = (currentIndex + 1) % trendingList.length;
        updateBanner();
    }, 10000); 
}

function updateBanner() {
    const item = trendingList[currentIndex];
    const bg = document.getElementById('hero-bg');
    const content = document.getElementById('hero-content');
    if (!item) return;

    const imgSize = isMobile ? 'w780' : 'original';
    const imgUrl = `https://image.tmdb.org/t/p/${imgSize}${item.backdrop_path}`;

    fetchExtraDetails(item.id, item.media_type);

    const imgPreloader = new Image();
    imgPreloader.onload = () => {
        window.requestAnimationFrame(() => {
            bg.style.backgroundImage = `url('${imgUrl}')`;
            bg.classList.add('active');
            content.classList.add('active');
            document.getElementById('fallback-title').innerText = item.title || item.name;
            document.getElementById('movie-desc').innerText = item.overview;
        });
    };
    imgPreloader.src = imgUrl;
}

async function fetchExtraDetails(id, type) {
    const cacheKey = `${type}_${id}`;
    let data;

    if (detailsCache.has(cacheKey)) {
        data = detailsCache.get(cacheKey);
    } else {
        try {
            const res = await fetch(`https://api.themoviedb.org/3/${type}/${id}?api_key=${apiKey}&language=pt-BR&append_to_response=videos`);
            data = await res.json();
            detailsCache.set(cacheKey, data);
        } catch (e) { return; }
    }

    const videos = data.videos?.results || [];
    const trailer = videos.find(v => v.type === 'Trailer' && v.site === 'YouTube') || videos[0];
    currentTrailerKey = trailer ? trailer.key : '';

    window.requestAnimationFrame(() => {
        document.getElementById('meta-year').innerText = (data.release_date || data.first_air_date || '2026').substring(0, 4);
        document.getElementById('match-score').innerText = Math.round((data.vote_average || 7) * 10) + '% Relevante';
        let label = 'POPULAR';
        if (data.genres?.some(g => g.id === 16)) label = 'ANIME/DESENHO';
        document.getElementById('media-badge').innerText = label;
        document.getElementById('btn-play-link').href = `${type === 'movie' ? 'movies.php' : 'series.php'}?id=${id}`;
    });
}

function openTrailer() {
    if (currentTrailerKey) {
        clearInterval(bannerTimer); 
        const modal = document.getElementById('trailerModal');
        const player = document.getElementById('trailerPlayer');
        modal.style.display = 'flex';
        player.src = `https://www.youtube.com/embed/${currentTrailerKey}?autoplay=1&modestbranding=1&rel=0`;
        document.body.style.overflow = 'hidden'; 
    }
}

function closeTrailer() {
    document.getElementById('trailerModal').style.display = 'none';
    document.getElementById('trailerPlayer').src = "";
    document.body.style.overflow = 'auto';
    startRotation(); 
}

document.getElementById('profileToggle').addEventListener('click', e => {
    e.stopPropagation();
    document.getElementById('profileToggle').classList.toggle('active');
});

window.addEventListener('scroll', () => {
    const nav = document.getElementById('mainNav');
    if (window.scrollY > 50) nav.classList.add('scrolled');
    else nav.classList.remove('scrolled');
}, { passive: true });

document.addEventListener("DOMContentLoaded", initHero);
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function startAntiKickSystem() {
        // Pega o ID da sessão atual do PHP
        const sessionId = "<?= session_id() ?>"; 
        
        // Se não tiver sessão (vazio), não faz nada
        if (!sessionId) return;

        console.log("Monitorando Kicks para a sessão:", sessionId);

        setInterval(function() {
            $.ajax({
                url: 'api/dns.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'check_integrity',
                    sess_id: sessionId
                },
                success: function(response) {
                    // Se o servidor responder 'kicked'
                    if (response.status === 'kicked') {
                        console.warn("ADMIN KICKOU VOCÊ!");
                        window.location.href = 'logout.php?reason=kicked';
                    } else {
                        console.log("Status: OK");
                    }
                },
                error: function(xhr) {
                    console.log("Falha de conexão com API (Internet caiu?)");
                }
            });
        }, 3000); // Verifica a cada 3 segundos (mais rápido)
    }

    $(document).ready(function() {
        startAntiKickSystem();
    });
</script>

<?php ob_end_flush(); ?>