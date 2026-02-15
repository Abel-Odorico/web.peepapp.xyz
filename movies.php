<?php
/**
 * MOVIES PAGE - ENGINE X-27 ZOOM MASTER
 * AUTO-ROTATE MOBILE (DOUBLE TAP) | UNIVERSAL ZOOM | HIGH PERFORMANCE
 * UPDATE: FIXED RESUME & CENTRALIZED LAYOUT
 */
ob_start();
session_start();
error_reporting(0);
include "includes/header.php";

$FinalCategoriesArray = array();
$GetCateGories = webtvpanel_CallApiRequest($hostURL . $bar . "player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories");

if ($GetCateGories["result"] == "success") {
    $FinalCategoriesArray = $GetCateGories["data"];
}
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#141414">

<link href="https://vjs.zencdn.net/8.10.0/video-js.css" rel="stylesheet" />
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700;900&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
    :root { 
        --bg: #0a0a0a;      
        --accent: #E50914;   
        --surface: #1a1a1a;
        --border: rgba(255,255,255,0.1);
        --smooth: cubic-bezier(0.4, 0, 0.2, 1);
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
    
    body {
        background-color: var(--bg) !important;
        font-family: 'Roboto', sans-serif;
        color: #fff; margin: 0; padding: 0;
        overflow-x: hidden; min-height: 100vh;
    }

    .sidebar, .sidenav, header, footer { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; padding: 0 !important; }

    /* --- CONTAINER CENTRALIZADO (NOVO) --- */
    /* Mantém o código original, apenas adiciona limite de largura e margem automática */
    .center-container {
        max-width: 1200px;
        margin: 0 auto;
        width: 100%;
        position: relative;
    }

    /* --- ESTILOS DE ZOOM ROBUSTOS (PC & MOBILE) --- */
    .video-js .vjs-tech { transition: object-fit 0.3s ease; }
    
    /* Classes de Zoom */
    .vjs-zoom-fit .vjs-tech { object-fit: contain !important; }   /* Padrão */
    .vjs-zoom-fill .vjs-tech { object-fit: fill !important; }     /* Esticar */
    .vjs-zoom-cover .vjs-tech { object-fit: cover !important; }   /* Zoom Recortado */

    /* Botão Customizado */
    .vjs-custom-zoom-btn { 
        cursor: pointer; font-size: 1.5em !important; 
        margin-top: 2px; color: #fff; opacity: 0.8;
    }
    .vjs-custom-zoom-btn:hover { opacity: 1; color: var(--accent); }

    /* NAVBAR TOP */
    .top-nav {
        position: fixed; top: 0; width: 100%; height: 70px;
        z-index: 1000; background: linear-gradient(to bottom, rgba(10,10,10,1) 10%, transparent 100%);
        backdrop-filter: blur(10px); display: flex; justify-content: center;
    }
    .top-nav-inner {
        width: 100%; max-width: 1200px; padding: 0 4%; 
        display: flex; align-items: center; justify-content: space-between;
    }

    /* PREMIUM FLOATING NAVBAR */
    .netflix-nav {
        position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%);
        width: 92%; max-width: 500px; height: 68px;
        background: rgba(23, 23, 23, 0.85);
        backdrop-filter: blur(25px) saturate(180%);
        -webkit-backdrop-filter: blur(25px) saturate(180%);
        display: none; justify-content: space-around; align-items: center;
        z-index: 5000; border: 1px solid var(--border);
        border-radius: 24px; box-shadow: 0 15px 35px rgba(0,0,0,0.6);
        padding: 0 10px;
    }

    .nav-item {
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: rgba(255,255,255,0.5); text-decoration: none; font-size: 10px; font-weight: 600;
        flex: 1; height: 100%; transition: all 0.3s var(--smooth); position: relative;
    }

    .nav-item i { font-size: 20px; margin-bottom: 4px; transition: transform 0.3s var(--smooth); }
    .nav-item.active { color: #fff; }
    .nav-item.active i { transform: translateY(-2px); color: var(--accent); }
    .nav-item.active::after {
        content: ''; position: absolute; bottom: 10px; width: 4px; height: 4px;
        background: var(--accent); border-radius: 50%; box-shadow: 0 0 10px var(--accent);
    }

    /* GRID */
    #MoviesGrid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 16px; padding: 20px 0 140px; list-style: none; margin: 0; }
    .movie-card { background: var(--surface); border-radius: 12px; overflow: hidden; aspect-ratio: 2/3; cursor: pointer; transition: transform 0.4s var(--smooth); border: 1px solid var(--border); position: relative; }
    .movie-card:hover { transform: scale(1.05); z-index: 10; border-color: var(--accent); }
    .poster-img { width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s; }
    .poster-img.loaded { opacity: 1; }

    /* PLAYER UI */
    #video-container { position: fixed; inset: 0; background: #000; z-index: 9999; display: none; }
    
    /* Feedback Visual do Double Tap (Agora ícone de rotação) */
    .tap-feedback { 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; 
        background: rgba(0,0,0,0.6); border-radius: 50%; opacity: 0; pointer-events: none; z-index: 10001; 
    }

    /* RESUME TOAST (CORRIGIDO) */
    #resume-toast { 
        position: absolute; /* Mudado para absolute para ficar dentro do container do vídeo */
        bottom: 110px; left: 50%; transform: translateX(-50%); 
        background: rgba(20,20,20,0.95); padding: 25px; border-radius: 16px; 
        border: 1px solid rgba(255,255,255,0.2); 
        z-index: 2147483647; /* Z-Index Máximo */
        display: none; text-align: center; width: 85%; max-width: 320px; 
        box-shadow: 0 20px 50px rgba(0,0,0,0.9); 
    }

    #catModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:7000; align-items:center; justify-content:center; backdrop-filter:blur(20px); }
    .cat-container { width:90%; max-width:500px; background:#1d1d1d; padding:25px; border-radius:24px; border: 1px solid var(--border); }
    .cat-list { display:grid; grid-template-columns:1fr 1fr; gap:10px; max-height:50vh; overflow-y:auto; }
    .cat-item { background:#2a2a2a; padding:15px; border-radius:12px; text-align:center; cursor:pointer; font-weight: 700; font-size: 11px; }

    #DetailModal { display: none; position: fixed; inset: 0; background: var(--bg); z-index: 6000; overflow-y: auto; }
    .detail-center { max-width: 1200px; margin: 0 auto; position: relative; } /* Centraliza modal */

    @media (max-width: 768px) {
        .netflix-nav { display: flex; }
        #MoviesGrid { grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 20px 4% 140px; }
        .top-nav-inner { padding: 0 15px; }
    }
</style>

<nav class="top-nav">
    <div class="top-nav-inner">
        <div style="display:flex; align-items:center; gap:15px;"><a href="dashboard.php" style="text-decoration:none; color:white; background:rgba(255,255,255,0.1); padding:8px 18px; border-radius:20px; font-weight:bold; font-size:14px;"><i class="fa fa-arrow-left"></i> Voltar</a></div>
        <div style="background:rgba(255,255,255,0.08); padding:10px 18px; border-radius:30px; display:flex; align-items:center;"><i class="fa fa-search" style="color:#aaa;"></i><input type="text" id="movieSearch" placeholder="Busca inteligente..." oninput="handleSearch(this.value)" style="background:transparent; border:none; color:#fff; margin-left:12px; font-size:14px; width: 150px;"></div>
    </div>
</nav>

<nav class="netflix-nav">
    <a href="dashboard.php" class="nav-item active"><i class="fa-solid fa-house"></i><span>Home</span></a>
    <a href="live.php" class="nav-item"><i class="fa-solid fa-tv"></i><span>Canais</span></a>
    <a href="series.php" class="nav-item"><i class="fa-solid fa-layer-group"></i><span>Séries</span></a>
    <a href="logout.php" class="nav-item"><i class="fa-solid fa-arrow-right-from-bracket"></i><span>Sair</span></a>
</nav>

<div class="center-container">
    <div class="content-header" style="padding: 100px 4% 20px; text-align:center;">
        <h1 id="CatTitle" style="font-weight:900; letter-spacing:-2px; margin-bottom: 18px; font-size: 2.2rem; text-transform: uppercase;">FILMES</h1>
        <button onclick="$('#catModal').css('display','flex').hide().fadeIn(250)" style="background:#fff; color:#000; border:none; padding:12px 30px; border-radius:30px; font-weight:900; cursor:pointer;">CATEGORIAS</button>
    </div>

    <ul id="MoviesGrid"></ul>
</div>

<div id="catModal">
    <div class="cat-container">
        <h3 style="text-align:center; margin-bottom:20px; font-weight: 900;">GÊNEROS</h3>
        <div class="cat-list">
            <div class="cat-item" onclick="loadMoviesData('all', 'Todos'); $('#catModal').fadeOut(200);">TODOS</div>
            <?php foreach ($FinalCategoriesArray as $cat) { 
                $id = is_object($cat) ? $cat->category_id : $cat['category_id'];
                $name = is_object($cat) ? $cat->category_name : $cat['category_name'];
                echo "<div class='cat-item' onclick=\"loadMoviesData('$id', '$name'); $('#catModal').fadeOut(200);\">".strtoupper($name)."</div>";
            } ?>
        </div>
        <button onclick="$('#catModal').fadeOut(200)" style="width:100%; margin-top:20px; background:var(--accent); color:#fff; border:none; padding:12px; border-radius:12px; font-weight:bold;">VOLTAR</button>
    </div>
</div>

<div id="DetailModal">
    <div class="detail-center">
        <div class="hero-info" id="MovieHero" style="height:55vh; background-size:cover; background-position:center; position:relative;">
            <div style="position:absolute; inset:0; background:linear-gradient(to top, var(--bg) 15%, transparent 100%);"></div>
            <div onclick="closeDetails()" style="position:absolute; top:20px; right:20px; font-size:32px; cursor:pointer;"><i class="fa fa-times-circle"></i></div>
        </div>
        <div style="padding:0 6% 120px; margin-top:-60px; position:relative;">
            <h1 id="MovieTitle" style="font-weight:900; font-size:2.8rem; margin:0;"></h1>
            <div id="MovieMeta" style="color:#46d369; font-weight:700; margin-top:15px; display:flex; gap:20px;"></div>
            <p id="MovieDesc" style="color:#ccc; font-size:17px; margin-top:20px; line-height:1.7; max-width:800px;"></p>
            <button id="startAction" style="background:#fff; color:#000; border:none; padding:18px 50px; border-radius:8px; font-weight:900; font-size:18px; margin-top:30px; cursor:pointer;"><i class="fa fa-play"></i> ASSISTIR AGORA</button>
        </div>
    </div>
</div>

<div id="video-container">
    <div onclick="closePlayer()" style="position:absolute; top:30px; left:30px; z-index:10000; cursor:pointer; color:white; background:rgba(0,0,0,0.5); width:50px; height:50px; border-radius:50%; display:flex; align-items:center; justify-content:center;"><i class="fa fa-arrow-left"></i></div>
    <div id="player-target" style="width:100%; height:100%;"></div>
    
    <div id="resume-toast">
        <p style="margin-bottom:15px; font-weight: 700; font-size: 16px;">Continuar de onde parou?</p>
        <div style="display:flex; gap:15px; justify-content:center;">
            <button id="resume-yes" style="background:var(--accent); color:#fff; border:none; padding:12px 25px; border-radius:8px; font-weight:800; cursor:pointer; flex:1;">SIM</button>
            <button id="resume-no" style="background:rgba(255,255,255,0.1); color:#fff; border:1px solid rgba(255,255,255,0.2); padding:12px 25px; border-radius:8px; font-weight:800; cursor:pointer; flex:1;">NÃO</button>
        </div>
    </div>

    <div id="tap-rotate" class="tap-feedback"><i class="fa fa-mobile-screen-button fa-2x fa-rotate-90"></i></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

<script>
const TMDB_KEY = '855ee921df930db2f2a9669eda268032';
let vjsPlayer = null;
let lastTap = 0;
let zoomState = 0; // 0=Fit, 1=Fill(Esticar), 2=Cover(Zoom)

function loadMoviesData(catID = 'all', catName = 'FILMES') {
    $('#CatTitle').text(catName.toUpperCase());
    $('#MoviesGrid').html('<div class="movie-card" style="opacity:0.2"></div>'.repeat(12));
    $.post("includes/ajax-control.php", { 
        action: 'getMoviesDataFromCategoryId', 
        categoryID: (catID === 'all' ? '' : catID), 
        hostURL: "<?php echo $XCStreamHostUrl . $bar; ?>" 
    }, function(resp) {
        const grid = $('#MoviesGrid').empty();
        const wrapper = $('<div>').html(resp);
        wrapper.find('li').each(function() {
            const item = $(this); const img = item.find('img').attr('src'); const title = item.text().trim();
            const idMatch = item.attr('onclick').match(/\d+/);
            const ext = item.attr('onclick').includes("','") ? item.attr('onclick').split("','")[1].replace(/[')\s]/g, "") : 'mp4';
            if(idMatch) grid.append(`<li class="movie-card" data-title="${title.toLowerCase()}" onclick="openDetails('${idMatch[0]}', '${title.replace(/'/g, "")}', '${img}', '${ext}')"><img src="${img}" class="poster-img" onload="this.classList.add('loaded')" loading="lazy"></li>`);
        });
    });
}

function startPlayer(id, ext) {
    const streamUrl = "<?php echo $XCStreamHostUrl . $bar; ?>movie/<?php echo $username; ?>/<?php echo $password; ?>/" + id + "." + (ext || 'mp4');
    const storageKey = 'resume_vod_' + id;
    const isMobile = window.innerWidth < 1024;
    
    $('#video-container').show();
    $('#player-target').html(`<video id="vjs-core" class="video-js vjs-big-play-centered" controls playsinline style="width:100%; height:100%;"></video>`);
    
    vjsPlayer = videojs('vjs-core', { 
        autoplay: true, 
        preload: 'auto',
        responsive: true,
        html5: { hls: { overrideNative: true } }, // Força controle no mobile
        controlBar: { children: [
            'playToggle', 'volumePanel', 'currentTimeDisplay', 'timeDivider', 
            'durationDisplay', 'progressControl', 'liveDisplay', 'remainingTimeDisplay', 
            'customControlSpacer', 'playbackRateMenuButton', 'subsCapsButton', 
            'audioTrackButton', 'fullscreenToggle'
        ]}
    });

    // --- ZOOM CONTROL UNIVERSAL (PC & MOBILE) ---
    const zoomBtn = vjsPlayer.controlBar.addChild('button', {}, 10);
    zoomBtn.addClass('vjs-custom-zoom-btn');
    zoomBtn.el().innerHTML = '<i class="fa fa-expand"></i>';
    zoomBtn.el().ontouchstart = function(e) { e.stopPropagation(); toggleZoom(); }; // Mobile
    zoomBtn.el().onclick = function(e) { toggleZoom(); }; // PC

    function toggleZoom() {
        zoomState = (zoomState + 1) % 3;
        const videoWrap = $(vjsPlayer.el());
        const btnIcon = $(zoomBtn.el()).find('i');
        
        videoWrap.removeClass('vjs-zoom-fit vjs-zoom-fill vjs-zoom-cover');
        
        if(zoomState === 0) {
            videoWrap.addClass('vjs-zoom-fit'); // Normal
            btnIcon.attr('class', 'fa fa-expand');
        } else if(zoomState === 1) {
            videoWrap.addClass('vjs-zoom-fill'); // Esticar
            btnIcon.attr('class', 'fa fa-arrows-left-right-to-line');
        } else {
            videoWrap.addClass('vjs-zoom-cover'); // Zoom/Recorte
            btnIcon.attr('class', 'fa fa-compress');
        }
    }

    vjsPlayer.src({ src: streamUrl, type: (ext === 'm3u8' ? 'application/x-mpegURL' : 'video/mp4') });
    
    vjsPlayer.ready(function() {
        if (!isMobile) {
            this.requestFullscreen();
        }

        // --- SISTEMA ONDE PAROU (CORRIGIDO) ---
        const savedTime = localStorage.getItem(storageKey);
        
        if (savedTime && parseFloat(savedTime) > 10) {
            setTimeout(() => {
                $('#resume-toast').fadeIn().css('display', 'flex').css('flex-direction', 'column');
            }, 1000);

            $('#resume-yes').off().on('click', () => { 
                vjsPlayer.currentTime(parseFloat(savedTime)); 
                vjsPlayer.play(); // Força o play
                $('#resume-toast').fadeOut(); 
            });

            $('#resume-no').off().on('click', () => { 
                localStorage.removeItem(storageKey); 
                $('#resume-toast').fadeOut(); 
            });
            
            setTimeout(() => $('#resume-toast').fadeOut(), 10000);
        }

        this.on('timeupdate', () => { 
            const dur = this.duration();
            const curr = this.currentTime();
            if(curr > 10 && (dur - curr) > 30) {
                localStorage.setItem(storageKey, curr); 
            } else if ((dur - curr) < 10) {
                localStorage.removeItem(storageKey); // Remove se acabou
            }
        });
    });

    // --- DOUBLE TAP PARA VIRAR TELA (MOBILE) ---
    vjsPlayer.on('touchstart', function(e) {
        if (e.target.closest('.vjs-control-bar')) return; // Ignora toques na barra de controle
        
        let now = new Date().getTime();
        let since = now - lastTap;
        
        if (since < 300 && since > 0) {
            // Double Tap Detectado - Alternar Tela Cheia / Rotação
            e.preventDefault();
            if (!document.fullscreenElement) {
                if(vjsPlayer.requestFullscreen) vjsPlayer.requestFullscreen();
                else if(vjsPlayer.el().webkitRequestFullscreen) vjsPlayer.el().webkitRequestFullscreen();
                
                // Tenta travar rotação se suportado
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('landscape').catch(()=>{});
                }
            } else {
                if(document.exitFullscreen) document.exitFullscreen();
                else if(document.webkitExitFullscreen) document.webkitExitFullscreen();
                
                if (screen.orientation && screen.orientation.unlock) {
                    screen.orientation.unlock();
                }
            }
            showFeedback('tap-rotate');
        }
        lastTap = now;
    });
}

function showFeedback(id) { $(`#${id}`).stop(true,true).animate({opacity: 1, transform: 'scale(1.2)'}, 200).animate({opacity: 0, transform: 'scale(1)'}, 200); }

function closePlayer() { 
    if(document.exitFullscreen) document.exitFullscreen().catch(()=>{});
    if(screen.orientation && screen.orientation.unlock) screen.orientation.unlock();
    $('#video-container').fadeOut(250, function() { if(vjsPlayer) vjsPlayer.dispose(); }); 
    zoomState = 0; // Reset zoom
}

function openDetails(id, title, img, ext) {
    $('#MovieTitle').text(title); $('#MovieHero').css('background-image', `url(${img})`);
    $('#DetailModal').fadeIn(300);
    $('#startAction').off().on('click', () => { closeDetails(); startPlayer(id, ext); });
    
    fetch(`https://api.themoviedb.org/3/search/movie?api_key=${TMDB_KEY}&query=${encodeURIComponent(title)}&language=pt-BR`).then(r => r.json()).then(data => {
        if(data.results && data.results[0]) {
            const m = data.results[0];
            $('#MovieDesc').text(m.overview || "Sinopse não disponível.");
            $('#MovieMeta').html(`<span>${m.release_date.split('-')[0]}</span> <span style='color:#46d369'>★ ${m.vote_average.toFixed(1)}</span>`);
        }
    });
}

function closeDetails() { $('#DetailModal').fadeOut(300); }
function handleSearch(v) { 
    const q = v.toLowerCase();
    $('.movie-card').each(function() { $(this).toggle($(this).data('title').includes(q)); });
}

$(document).ready(() => loadMoviesData('all'));
</script>

<?php ob_end_flush(); ?>