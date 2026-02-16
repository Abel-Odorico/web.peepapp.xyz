<?php
/**
 * SERIES PAGE - ULTIMATE EXPERIENCE 2026 (120 FPS + SMART RESUME + SUBTITLES)
 * DESIGN UNIFICADO: MODAL DE GÊNEROS RECUPERADO + BOTÃO ZOOM + FIX DETALHES
 * MOBILE NAV AJUSTADA (MAIOR)
 */
ob_start();
session_start();
error_reporting(0);
include "includes/header.php";

$FinalCategoriesArray = array();
$GetCateGories = webtvpanel_CallApiRequest($hostURL . $bar . "player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_categories");

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
        --border: rgba(255,255,255,0.08);
        --smooth: cubic-bezier(0.25, 0.1, 0.25, 1);
        
        /* --- AJUSTE FEITO AQUI: AUMENTEI DE 65px PARA 80px --- */
        --nav-height: 80px; 
    }

    * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; outline: none; }
    
    body {
        background-color: var(--bg) !important;
        font-family: 'Roboto', sans-serif;
        color: #fff; margin: 0; padding: 0;
        overflow-x: hidden; -webkit-font-smoothing: antialiased;
    }

    .sidebar, .sidenav, header, footer { display: none !important; }
    .main-content { margin: 0 !important; width: 100% !important; padding: 0 !important; }

    /* ACELERAÇÃO GPU 120 FPS */
    .series-card, .top-nav, #DetailModal, #catModal { transform: translateZ(0); will-change: transform, opacity; }

    /* --- ADIÇÕES: ZOOM & PLAYER --- */
    .video-js .vjs-tech { transition: object-fit 0.3s ease; }
    .vjs-zoom-fit .vjs-tech { object-fit: contain !important; }
    .vjs-zoom-fill .vjs-tech { object-fit: fill !important; }
    .vjs-zoom-cover .vjs-tech { object-fit: cover !important; }

    .vjs-custom-zoom-btn { 
        cursor: pointer; font-size: 1.5em !important; margin-top: 2px;
        color: #fff; opacity: 0.8; 
    }
    .vjs-custom-zoom-btn:hover { opacity: 1; color: var(--accent); }

    .tap-feedback { 
        position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
        width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; 
        background: rgba(0,0,0,0.6); border-radius: 50%; opacity: 0; pointer-events: none; z-index: 10001; 
    }

    /* ESTILO DO RESUME TOAST (ONDE PAROU) */
    #resume-toast {
        position: fixed; bottom: 100px; left: 50%; transform: translateX(-50%);
        background: rgba(20, 20, 20, 0.95); border: 1px solid var(--border);
        padding: 20px 25px; border-radius: 16px; z-index: 10020;
        display: none; text-align: center; width: 90%; max-width: 350px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.8); backdrop-filter: blur(10px);
    }
    #resume-toast h4 { margin: 0 0 15px 0; font-size: 14px; font-weight: 700; color: #fff; }
    .resume-actions { display: flex; gap: 10px; justify-content: center; }
    .resume-btn { border: none; padding: 10px 20px; border-radius: 8px; font-weight: bold; cursor: pointer; flex: 1; font-size: 12px; }
    .btn-yes { background: var(--accent); color: white; }
    .btn-no { background: rgba(255,255,255,0.1); color: white; }

    /* TOP NAV */
    .top-nav {
        position: fixed; top: 0; width: 100%; height: 70px;
        padding: 0 4%; display: flex; align-items: center; justify-content: space-between;
        z-index: 1000; background: linear-gradient(to bottom, rgba(10,10,10,1) 30%, transparent 100%);
        backdrop-filter: blur(10px);
    }

    .search-container {
        background: rgba(255,255,255,0.08); padding: 10px 18px; border-radius: 30px; 
        display: flex; align-items: center; border: 1px solid var(--border);
    }

    /* GRID */
    #SeriesGrid {
        display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px; padding: 100px 5% 120px; list-style: none;
    }

    .series-card {
        background: var(--surface); border-radius: 12px; overflow: hidden;
        aspect-ratio: 2/3; cursor: pointer; position: relative;
        transition: transform 0.3s var(--smooth); border: 1px solid var(--border);
    }
    .series-card:hover { transform: scale(1.05); border-color: var(--accent); z-index: 5; }
    .poster-img { width: 100%; height: 100%; object-fit: cover; opacity: 0; transition: opacity 0.5s; }
    .poster-img.loaded { opacity: 1; }

    /* MODAL GÊNEROS */
    #catModal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:7000; align-items:center; justify-content:center; backdrop-filter:blur(20px); }
    .cat-container { width:90%; max-width:500px; background:#1d1d1d; padding:25px; border-radius:24px; border:1px solid var(--border); position: relative; }
    .cat-list { display:grid; grid-template-columns:1fr 1fr; gap:10px; max-height:50vh; overflow-y:auto; margin-top:15px; }
    .cat-item { background:#2a2a2a; padding:15px; border-radius:12px; text-align:center; cursor:pointer; font-weight: 700; transition: 0.2s; font-size: 11px; text-transform: uppercase; }
    .cat-item:hover { background: var(--accent); }
    .close-cat-btn { position: absolute; top: -15px; right: -15px; background: var(--accent); width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; border: 2px solid #fff; z-index: 10; }

    /* DETAIL MODAL */
    #DetailModal { display: none; position: fixed; inset: 0; background: var(--bg); z-index: 6000; overflow-y: auto; }
    .hero-info { height: 50vh; width: 100%; background-size: cover; background-position: center; display: flex; align-items: flex-end; padding: 50px 5%; position: relative; }
    .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to top, var(--bg) 15%, transparent 100%); }
    .episode-item { display: flex; align-items: center; gap: 15px; padding: 15px; background: rgba(255,255,255,0.05); margin-bottom: 10px; border-radius: 12px; cursor: pointer; transition: 0.2s; border: 1px solid transparent; }
    .episode-item:hover { background: rgba(255,255,255,0.1); border-color: var(--accent); }

    /* PLAYER CONTAINER */
    #video-container { position: fixed; inset: 0; background: #000; z-index: 9999; display: none; }

    /* MOBILE NAV (Agora mais alta com var(--nav-height)) */
    .mobile-nav { 
        position: fixed; bottom: 0; left: 0; width: 100%; 
        height: var(--nav-height); 
        background: #000; display: none; justify-content: space-around; align-items: center; 
        z-index: 5000; border-top: 1px solid var(--border); 
    }
    .nav-link { color: #888; text-decoration: none; display: flex; flex-direction: column; align-items: center; font-size: 10px; }
    .nav-link.active { color: #fff; }

    @media (max-width: 768px) {
        .mobile-nav { display: flex; }
        #SeriesGrid { grid-template-columns: repeat(3, 1fr); gap: 10px; padding-top: 80px; }
        .top-nav .back-btn { display: none; }
        .search-container input { width: 130px; }
    }
</style>

<nav class="top-nav">
    <a href="dashboard.php" class="back-btn" style="text-decoration:none; color:#fff; font-weight:bold; background:rgba(255,255,255,0.1); padding:8px 15px; border-radius:20px;"><i class="fa fa-arrow-left"></i> Voltar</a>
    <div class="search-container">
        <i class="fa fa-search" style="color:#666"></i>
        <input type="text" id="seriesSearch" placeholder="Buscar série..." oninput="smartSearch()" style="background:transparent; border:none; color:#fff; margin-left:10px; outline:none;">
    </div>
    <button onclick="$('#catModal').css('display','flex').hide().fadeIn(250)" style="background:var(--accent); color:#fff; border:none; padding:10px 20px; border-radius:30px; font-weight:900; cursor:pointer; font-size:12px;">GÊNEROS</button>
</nav>

<ul id="SeriesGrid"></ul>

<div id="catModal">
    <div class="cat-container">
        <div class="close-cat-btn" onclick="$('#catModal').fadeOut(250)"><i class="fa fa-times"></i></div>
        <h3 style="text-align:center; margin:0; font-weight: 900;">FILTRAR POR GÊNERO</h3>
        <div class="cat-list">
            <div class="cat-item" onclick="loadSeriesData('all'); $('#catModal').fadeOut(200);">TODOS</div>
            <?php foreach ($FinalCategoriesArray as $cat) { 
                $id = is_object($cat) ? $cat->category_id : $cat['category_id'];
                $name = is_object($cat) ? $cat->category_name : $cat['category_name'];
                echo "<div class='cat-item' onclick=\"loadSeriesData('$id'); $('#catModal').fadeOut(200);\">".strtoupper($name)."</div>";
            } ?>
        </div>
    </div>
</div>

<div id="DetailModal">
    <div class="hero-info" id="SeriesHero">
        <div class="hero-overlay"></div>
        <div style="position:relative; z-index:10; width: 100%;">
            <h1 id="SeriesTitle" style="font-size: clamp(1.8rem, 5vw, 3.5rem); margin:0; font-weight:900;"></h1>
            <p id="SeriesDesc" style="max-width:700px; color:#ccc; margin: 15px 0; font-size: 14px;"></p>
        </div>
        <div onclick="closeDetails()" style="position:absolute; top:30px; right:30px; font-size:30px; cursor:pointer; z-index:20;"><i class="fa fa-times-circle"></i></div>
    </div>
    <div style="padding: 0 5% 100px;" id="EpisodesBox"></div>
</div>

<div id="video-container">
    <div onclick="closePlayer()" style="position:absolute; top:30px; left:30px; z-index:10005; cursor:pointer; color:#fff; filter: drop-shadow(0 0 5px #000);"><i class="fa fa-arrow-left fa-2x"></i></div>
    <div id="player-target" style="width:100%; height:100%;"></div>
    
    <div id="tap-rotate" class="tap-feedback"><i class="fa fa-mobile-screen-button fa-2x fa-rotate-90"></i></div>

    <div id="resume-toast">
        <h4>Continuar de onde parou?</h4>
        <div class="resume-actions">
            <button id="resume-yes" class="resume-btn btn-yes">SIM</button>
            <button id="resume-no" class="resume-btn btn-no">NÃO</button>
        </div>
    </div>
</div>

<nav class="mobile-nav">
    <a href="dashboard.php" class="nav-link"><i class="fa fa-home fa-lg"></i><span>Home</span></a>
    <a href="movies.php" class="nav-link"><i class="fa fa-film fa-lg"></i><span>Filmes</span></a>
    <a href="series.php" class="nav-link active"><i class="fa fa-layer-group fa-lg"></i><span>Séries</span></a>
    <a href="logout.php" class="nav-link"><i class="fa fa-sign-out fa-lg"></i><span>Sair</span></a>
</nav>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://vjs.zencdn.net/8.10.0/video.min.js"></script>

<script>
let vjsPlayer = null;
let lastTap = 0;
let zoomState = 0; // 0=Fit, 1=Fill, 2=Cover

function smartSearch() {
    const q = $('#seriesSearch').val().toLowerCase();
    $('.series-card').each(function() {
        $(this).toggle($(this).data('title').includes(q));
    });
}

function loadSeriesData(catID = 'all') {
    $('#SeriesGrid').html('<p style="padding:50px; text-align:center;">Buscando séries...</p>');
    $.post("includes/ajax-control.php", { action: 'GetSeriesByCateGoryId', categoryID: (catID === 'all' ? '' : catID), hostURL: "<?php echo $XCStreamHostUrl . $bar; ?>" }, function(resp) {
        const grid = $('#SeriesGrid').empty();
        const wrapper = $('<div>').html(resp);
        wrapper.find('li').each(function() {
            const item = $(this); const img = item.find('img').attr('src'); const title = item.text().trim();
            const idMatch = item.attr('onclick').match(/\d+/);
            if(idMatch) {
                grid.append(`<li class="series-card" data-title="${title.toLowerCase()}" onclick="openDetails('${idMatch[0]}', '${title.replace(/'/g, "")}', '${img}')">
                    <img src="${img}" class="poster-img" onload="this.classList.add('loaded')">
                </li>`);
            }
        });
    });
}

function openDetails(id, title, img) {
    $('#SeriesTitle').text(title);
    $('#SeriesHero').css('background-image', `url(${img})`);
    $('#SeriesDesc').text("Carregando episódios...");
    $('#EpisodesBox').empty();
    $('#DetailModal').fadeIn(300);

    $.post("includes/ajax-control.php", { action: 'getSeriesInfo', seriesID: id, hostURL: "<?php echo $XCStreamHostUrl . $bar; ?>" }, function(res) {
        const temp = $('<div>').html(res);
        let seasons = {};
        temp.find('a').each(function() {
            const epT = $(this).text().trim(); const epId = $(this).data('episid'); const epExt = $(this).data('type') || 'mp4';
            if(!epId) return;
            const match = epT.match(/(?:S|T)\s?(\d+)/i);
            const sNum = match ? parseInt(match[1]) : 1;
            if(!seasons[sNum]) seasons[sNum] = [];
            seasons[sNum].push({ id: epId, title: epT, ext: epExt });
        });

        let html = '';
        Object.keys(seasons).sort((a,b) => a-b).forEach(s => {
            html += `<h3 style="color:var(--accent); margin: 30px 0 10px; font-weight:900;">Temporada ${s}</h3>`;
            seasons[s].forEach(ep => {
                html += `<div class="episode-item" onclick="startPlayer('${ep.id}', '${ep.ext}')">
                    <i class="fa fa-play-circle fa-2x" style="color:var(--accent)"></i>
                    <span style="font-weight:bold">${ep.title}</span>
                </div>`;
            });
        });
        $('#EpisodesBox').html(html);
        $('#SeriesDesc').text("Selecione um episódio para reproduzir.");
    });
}

function startPlayer(id, ext) {
    const streamUrl = "<?php echo $XCStreamHostUrl . $bar; ?>series/<?php echo $username; ?>/<?php echo $password; ?>/" + id + "." + ext;
    const storageKey = 'resume_series_' + id; // Chave única para salvar o tempo

    $('#video-container').fadeIn(200);
    $('#player-target').html(`<video id="vjs-core" class="video-js vjs-big-play-centered" controls playsinline style="width:100%; height:100%;"></video>`);
    
    // Inicialização com suporte a HLS nativo override para CSS funcionar e Legendas
    vjsPlayer = videojs('vjs-core', { 
        autoplay: true,
        html5: { 
            hls: { overrideNative: true }, // Importante para controle de legendas em M3U8
            nativeTextTracks: false // Tenta forçar o UI de legenda do VideoJS
        },
        controlBar: { children: [
            'playToggle', 'volumePanel', 'currentTimeDisplay', 'timeDivider', 
            'durationDisplay', 'progressControl', 'liveDisplay', 'remainingTimeDisplay', 
            'customControlSpacer', 'playbackRateMenuButton', 'subsCapsButton', // Botão de Legendas
            'audioTrackButton', 'fullscreenToggle'
        ]}
    });

    // --- ADIÇÃO: BOTÃO ZOOM DENTRO DA BARRA ---
    const zoomBtn = vjsPlayer.controlBar.addChild('button', {}, 10);
    zoomBtn.addClass('vjs-custom-zoom-btn');
    zoomBtn.el().innerHTML = '<i class="fa fa-expand"></i>';
    
    // Funciona no PC e Mobile (Touch)
    const handleZoom = (e) => { 
        e.stopPropagation(); 
        toggleZoomInternal();
    };
    zoomBtn.el().ontouchstart = handleZoom;
    zoomBtn.el().onclick = handleZoom;

    vjsPlayer.src({ src: streamUrl, type: (ext === 'm3u8' ? 'application/x-mpegURL' : 'video/mp4') });

    // --- LÓGICA DO RESUME (ONDE PAROU) E LEGENDAS ---
    vjsPlayer.ready(function() {
        // 1. Verificar Tempo Salvo
        const savedTime = localStorage.getItem(storageKey);
        if (savedTime && parseFloat(savedTime) > 10) {
            vjsPlayer.pause(); // Pausa para perguntar
            $('#resume-toast').fadeIn();
            
            $('#resume-yes').off().on('click', function() {
                vjsPlayer.currentTime(parseFloat(savedTime));
                vjsPlayer.play();
                $('#resume-toast').fadeOut();
            });

            $('#resume-no').off().on('click', function() {
                vjsPlayer.currentTime(0);
                vjsPlayer.play();
                localStorage.removeItem(storageKey); // Limpa se user não quiser
                $('#resume-toast').fadeOut();
            });
        }

        // 2. Salvar Tempo Automaticamente
        this.on('timeupdate', function() {
            if(this.currentTime() > 10) {
                localStorage.setItem(storageKey, this.currentTime());
            }
        });

        // 3. Auto-Ativar Legendas (se disponível no stream)
        // Isso tenta encontrar a primeira trilha de legenda e ativar
        var tracks = this.textTracks();
        if(tracks && tracks.length > 0) {
            for (var i = 0; i < tracks.length; i++) {
                var track = tracks[i];
                if (track.kind === 'captions' || track.kind === 'subtitles') {
                    track.mode = 'showing'; // Força exibir a primeira legenda encontrada
                    break;
                }
            }
        }
    });

    // --- ADIÇÃO: DOUBLE TAP MOBILE ---
    vjsPlayer.on('touchstart', function(e) {
        if (e.target.closest('.vjs-control-bar')) return;
        
        let now = new Date().getTime();
        let since = now - lastTap;
        
        if (since < 300 && since > 0) {
            e.preventDefault();
            // Alternar Fullscreen / Paisagem
            if (!document.fullscreenElement) {
                if(vjsPlayer.requestFullscreen) vjsPlayer.requestFullscreen();
                if (screen.orientation && screen.orientation.lock) {
                    screen.orientation.lock('landscape').catch(()=>{});
                }
            } else {
                if(document.exitFullscreen) document.exitFullscreen();
                if (screen.orientation && screen.orientation.unlock) {
                    screen.orientation.unlock();
                }
            }
            // Feedback Visual
            $('#tap-rotate').stop(true,true).animate({opacity: 1, transform: 'scale(1.2)'}, 200).animate({opacity: 0, transform: 'scale(1)'}, 200);
        }
        lastTap = now;
    });
}

function toggleZoomInternal() {
    zoomState = (zoomState + 1) % 3;
    const videoWrap = $(vjsPlayer.el());
    const btnIcon = $('.vjs-custom-zoom-btn i');
    
    videoWrap.removeClass('vjs-zoom-fit vjs-zoom-fill vjs-zoom-cover');
    
    if(zoomState === 0) {
        videoWrap.addClass('vjs-zoom-fit');
        btnIcon.attr('class', 'fa fa-expand');
    } else if(zoomState === 1) {
        videoWrap.addClass('vjs-zoom-fill');
        btnIcon.attr('class', 'fa fa-arrows-left-right-to-line');
    } else {
        videoWrap.addClass('vjs-zoom-cover');
        btnIcon.attr('class', 'fa fa-compress');
    }
}

function closePlayer() { 
    if(document.exitFullscreen) document.exitFullscreen().catch(()=>{});
    if(screen.orientation && screen.orientation.unlock) screen.orientation.unlock();
    if(vjsPlayer) vjsPlayer.dispose(); 
    $('#video-container').fadeOut(200); 
    $('#resume-toast').hide(); // Esconde toast se fechar player
    zoomState = 0;
}

function closeDetails() { $('#DetailModal').fadeOut(300); }
$(document).ready(() => loadSeriesData('all'));
</script>

<?php ob_end_flush(); ?>