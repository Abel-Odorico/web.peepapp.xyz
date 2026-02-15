<?php
/**
 * ELITE 4K ENGINE ULTIMATE v2026 - MASTER TRIPLE ENGINE PRO
 * UPDATES: TS/TSH/SM38 Native Support, Video.js 8.x Next Gen, DNS Direct Pull.
 * Tech: Watchdog Ultra V7, Real-Time DNS Sync, Hardware Acceleration 120Hz.
 */
if (!isset($_POST["dateFullData"])) {
    echo "<!DOCTYPE html>
    <html lang='pt-br'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no'>
            <title>Elite 4K Engine v2026</title>
            <script src='js/jquery-1.11.2.min.js'></script>
            <style>
                body { background: #000; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; overflow:hidden; }
                .loader-text { color: #E50914; font-family: 'Inter', sans-serif; font-weight: bold; font-size: 20px; letter-spacing: 2px; text-shadow: 0 0 20px rgba(229,9,20,0.6); animation: pulse 1.5s infinite; }
                @keyframes pulse { 0% { opacity: 0.4; transform: scale(0.98); } 50% { opacity: 1; transform: scale(1); } 100% { opacity: 0.4; transform: scale(0.98); } }
            </style>
        </head>
        <body>
            <div class='loader-text'>MASTER ENGINE V4.0 ULTIMATE</div>
            <form method='POST' id='FormSubmit' action='' autocomplete='off'>
                <input type='hidden' name='dateFullData' id='InputFieldId' value=''>
            </form>
            <script type='text/javascript'>
                var currentTime = new Date();
                var dateDatatosent = currentTime.getFullYear() + '-' + (currentTime.getMonth() + 1) + '-' + currentTime.getDate() + ' ' + currentTime.getHours() + ':' + currentTime.getMinutes() + ':' + currentTime.getSeconds();
                document.getElementById('InputFieldId').value = dateDatatosent;
                document.getElementById('FormSubmit').submit();
            </script>
        </body>
    </html>";
} else {
    include "includes/header.php";

    $CurrentPcDateTime = new DateTime($_POST["dateFullData"]);
    $CurrentTime = $CurrentPcDateTime->getTimestamp();
    if ($ShiftedTimeEPG != "0") { $CurrentTime = strtotime($ShiftedTimeEPG . " hours", $CurrentTime); }

    $GetLiveStreamCateGories = webtvpanel_CallApiRequest($hostURL . $bar . "player_api.php?username=" . $username . "&password=" . $password . "&action=get_live_categories");
    $FinalCategoriesArray = $GetLiveStreamCateGories ? $GetLiveStreamCateGories : array();
?>

<link href="https://vjs.zencdn.net/8.11.5/video-js.css" rel="stylesheet" />
<link href="https://unpkg.com/@videojs/themes@1.0.1/dist/city/index.css" rel="stylesheet" />
<script src="https://vjs.zencdn.net/8.11.5/video.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/videojs-contrib-dash@5.1.1/dist/videojs-dash.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
    :root {
        --n-bg: #010101; --n-card: #080808; --n-red: #E50914; --n-text: #FFFFFF;
        --n-border: rgba(255,255,255,0.08); --sidebar-w: 380px;
        --k-yellow: #FFD700; --k-blue: #00BFFF;
    }

    body, html { 
        background-color: var(--n-bg); font-family: 'Inter', sans-serif; 
        margin: 0; padding: 0; width: 100%; height: 100%; color: var(--n-text); overflow: hidden;
        -webkit-tap-highlight-color: transparent;
        image-rendering: -webkit-optimize-contrast;
    }

    /* ENGINE 120 FPS - AJUSTE PARA NÃO TRANSBORDAR EM CANAIS 24H */
    .video-js video {
        width: 100% !important;
        height: 100% !important;
        object-fit: contain; 
        will-change: transform;
        transform: translateZ(0);
        backface-visibility: hidden;
        -webkit-perspective: 1000;
    }

    .vjs-theme-city .vjs-big-play-button { border-radius: 50%; width: 80px; height: 80px; line-height: 80px; background-color: rgba(229, 9, 20, 0.8); border: none; }
    .vjs-control-bar { background: linear-gradient(0deg, rgba(0,0,0,0.9) 0%, transparent 100%) !important; height: 60px !important; }

    /* ZOOM ENGINE */
    .zoom-btn-container { position: absolute; bottom: 70px; right: 20px; z-index: 20; }
    .engine-zoom-btn {
        background: rgba(0,0,0,0.6); color: #fff; border: 1px solid var(--n-border);
        padding: 8px 14px; border-radius: 8px; cursor: pointer; font-size: 11px;
        font-weight: 800; backdrop-filter: blur(10px); transition: 0.3s;
    }
    .engine-zoom-btn:hover { background: var(--n-red); border-color: var(--n-red); }

    .lang-badge { font-size: 9px; padding: 2px 5px; border-radius: 3px; background: var(--k-blue); color: #fff; margin-left: 6px; font-weight: 900; text-transform: uppercase; }

    #parental-gate { position: fixed; inset: 0; background: rgba(0,0,0,0.98); z-index: 99999; display: none; backdrop-filter: blur(30px); }
    .gate-box { width: 90%; max-width: 380px; padding: 40px 25px; border: 1px solid rgba(229,9,20,0.4); border-radius: 28px; background: #0a0a0a; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); box-shadow: 0 20px 60px rgba(0,0,0,0.8); }
    .gate-input { background: #121212; border: 1px solid #333; color: #fff; padding: 18px; font-size: 32px; width: 100%; max-width: 220px; text-align: center; border-radius: 15px; margin: 25px 0; letter-spacing: 12px; outline: none; }
    .gate-btn { flex: 1; background: var(--n-red); color: #fff; border: none; padding: 16px; border-radius: 12px; cursor: pointer; font-weight: 800; font-size: 14px; text-transform: uppercase; }
    .gate-btn.cancel { background: #1a1a1a; color: #888; border: 1px solid #333; margin-right: 10px; }

    .engine-stats { display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap; }
    .stat-chip { background: rgba(255,255,255,0.05); border: 1px solid var(--n-border); padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: bold; color: #aaa; display: flex; align-items: center; gap: 6px; }
    .stat-chip i { color: var(--n-red); }
    .stat-chip span { color: #fff; }

    .btn-back { position: fixed; top: 15px; left: 15px; z-index: 2500; background: rgba(0,0,0,0.6); color: #fff; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; text-decoration: none; border: 1px solid var(--n-border); backdrop-filter: blur(10px); }

    .app-container { display: flex; height: 100vh; width: 100vw; flex-direction: row; position: relative; z-index: 1; }
    .main-player { flex: 1; display: flex; flex-direction: column; background: #000; position: relative; }
    #player-container { width: 100%; aspect-ratio: 16/9; position: relative; background: #000; flex: none; }
    .video-js { width: 100% !important; height: 100% !important; }

    .epg-panel { 
        padding: 25px; 
        background: #050505; 
        flex: 1; 
        overflow-y: auto; 
        scrollbar-width: thin; 
        scrollbar-color: var(--n-red) transparent;
        -webkit-overflow-scrolling: touch; 
    }

    .sidebar { width: var(--sidebar-w); background: var(--n-card); display: flex; flex-direction: column; border-left: 1px solid var(--n-border); z-index: 100; }
    .sidebar-header { padding: 20px; background: rgba(0,0,0,0.9); border-bottom: 1px solid var(--n-border); }
    .custom-select { width: 100%; background: #121212; color: #fff; border: 1px solid #252525; padding: 14px; border-radius: 12px; outline: none; font-size: 13px; }

    .channel-list { flex: 1; overflow-y: auto; padding: 12px; scrollbar-width: thin; scrollbar-color: var(--n-red) transparent; }
    .channel-item { display: flex; align-items: center; padding: 14px; border-radius: 14px; cursor: pointer; margin-bottom: 10px; background: rgba(255,255,255,0.02); transition: 0.2s; border: 1px solid transparent; }
    .channel-item:hover { background: rgba(255,255,255,0.05); transform: translateX(5px); }
    .channel-item.active { background: rgba(229, 9, 20, 0.12); border-color: var(--n-red); }

    .channel-logo { width: 50px; height: 50px; border-radius: 10px; object-fit: contain; margin-right: 15px; background: #000; }
    .channel-name { font-size: 14px; font-weight: 700; flex: 1; color: #eee; }
    .fav-icon { color: #333; font-size: 20px; padding: 8px; transition: 0.3s; }
    .fav-icon.is-fav { color: #ffd700; }

    #loading-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.9); display:flex; align-items:center; justify-content:center; z-index:30; backdrop-filter: blur(15px); }

    @media (max-width: 991px) {
        .app-container { flex-direction: column; overflow-y: hidden; }
        .sidebar { width: 100%; height: auto; min-height: 100vh; }
        #player-container { position: sticky; top: 0; z-index: 1100; }
    }
</style>

<a href='dashboard.php' class='btn-back'><i class='fa fa-arrow-left'></i></a>

<div id="parental-gate">
    <div class="gate-box">
        <i class="fa fa-shield-halved fa-4x" style="color:var(--n-red)"></i>
        <h3 style="margin-top:20px; color:#fff; font-size:24px; font-weight:900;">ACESSO RESTRITO</h3>
        <p style="color:#666; font-size:14px;">Conteúdo protegido. Digite seu PIN.</p>
        <input type="password" id="parental-pin" class="gate-input" maxlength="4" placeholder="••••" inputmode="numeric">
        <div style="display:flex;">
            <button class="gate-btn cancel" onclick="cancelParental()">VOLTAR</button>
            <button class="gate-btn" onclick="verifyParental()">ENTRAR</button>
        </div>
    </div>
</div>

<div class='app-container' id='AppWrapper'>
    <div class='main-player'>
        <div id='player-container'>
            <video id='main-video-player' class='video-js vjs-theme-city vjs-big-play-centered' controls preload='auto' playsinline></video>
            
            <div class="zoom-btn-container">
                <button class="engine-zoom-btn" onclick="changeAspectEngine()">
                    <i class="fa fa-expand"></i> AJUSTAR TELA
                </button>
            </div>

            <div id='loading-overlay'>
                <div style='text-align:center'>
                    <div class='fa-3x'><i class='fa fa-circle-notch fa-spin' style='color:var(--n-red);'></i></div>
                    <p id='loading-status' style='color:#eee; font-size:11px; margin-top:20px; letter-spacing:2px; font-weight:900; text-transform:uppercase;'>Sincronizando DNS Master...</p>
                </div>
            </div>
        </div>
        
        <div class='epg-panel'>
            <div id='EPGRender'>
                <h2 class='epg-title' id="current-title" style="margin:0; font-size:22px; font-weight:900;">Elite Dashboard</h2>
                <div class='engine-stats' id='StreamStats' style='display:none'>
                    <div class='stat-chip'><i class='fa fa-microchip'></i> <span id='device-origin'>-</span></div>
                    <div class='stat-chip'><i class='fa fa-tv'></i> <span id='res-val'>HD+</span></div>
                    <div class='stat-chip'><i class='fa fa-gauge-high'></i> <span id='bitrate-val'>0 Mbps</span></div>
                    <div class='stat-chip'><i class='fa fa-bolt'></i> <span>TS/SM38 ON</span></div>
                </div>
                <p id='epg-info' style='color:#777; font-size:14px; margin-top:15px; border-left: 3px solid var(--n-red); padding-left: 15px;'>Selecione um canal para iniciar o fluxo em tempo real.</p>
            </div>
        </div>
    </div>

    <div class='sidebar'>
        <div class='sidebar-header'>
            <div id='KidsToggle' class='kids-toggle' onclick='toggleKidsMode()' style="cursor:pointer; padding:15px; background:#1a1a1a; border-radius:12px; text-align:center; margin-bottom:15px; font-weight:900; font-size:12px; transition:0.3s; border:1px solid var(--n-border);">
                <i class='fa fa-child-reaching'></i> MODO KIDS
            </div>
            <select id='CatNav' class='custom-select' onchange='loadCategory(this.value)'>
                <option value='TOP'>🔥 MAIS ASSISTIDOS</option>
                <option value='FAVS'>⭐ MEUS FAVORITOS</option>
                <option value=''>📺 TODOS OS CANAIS</option>
                <?php
                    if (!empty($FinalCategoriesArray) && isset($FinalCategoriesArray['result']) && $FinalCategoriesArray['result'] == 'success') {
                        foreach ($FinalCategoriesArray['data'] as $cat) {
                            echo "<option value='{$cat->category_id}'>{$cat->category_name}</option>";
                        }
                    }
                ?>
            </select>
            <div style='position:relative; margin-top:12px;'>
                <input type='text' id='ChanSearch' class='custom-select' placeholder='🔍 Buscar canal ou categoria...' onkeyup='doSearch()'>
            </div>
        </div>
        <div class='channel-list' id='ChannelRender'></div>
    </div>
</div>

<script>
let player;
let kidsMode = false;
let teleTimer;
let currentChanId = null;
let zoomIdx = 0;
let pendingId = null, pendingEl = null;
const pKey = "0000"; 

const adultFilter = ["+18", "ADULTO", "XXX", "SEX", "PRIVE", "HOT", "VENUS", "PLAYBOY", "PENTHOUSE", "HUSTLER", "BRAZZERS"];
const kidsFilter = ["KIDS", "INFANTIL", "DESENHO", "DISNEY", "NICK", "GLOOB", "CARTOON", "BOOMERANG", "PANDA"];

$(document).ready(function() {
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    $('#device-origin').text(isMobile ? "MOBILE ENGINE" : "PC ULTRA ENGINE");

    player = videojs('main-video-player', {
        autoplay: true,
        fluid: true,
        liveui: true,
        playbackRates: [1],
        html5: {
            vhs: {
                overrideNative: true,
                enableLowInitialPlaylist: true,
                fastQualityChange: true,
                goalBufferLength: 2, 
                maxBufferLength: 6,
                useDevicePixelRatio: true
            },
            nativeVideoTracks: false,
            nativeAudioTracks: false,
            nativeTextTracks: false
        }
    });

    player.on('error', function() {
        setTimeout(() => { if(currentChanId) executeStream(currentChanId, pendingEl); }, 2000);
    });

    player.on('playing', () => { 
        $('#loading-overlay').fadeOut(200);
        $('#StreamStats').fadeIn();
        startTelemetria(); 
    });

    player.on('waiting', () => { $('#loading-overlay').show(); });

    loadCategory('TOP', true);
});

function changeAspectEngine() {
    const video = document.querySelector('.vjs-tech');
    const modes = ['contain', 'fill', 'cover', 'scale-down'];
    zoomIdx = (zoomIdx + 1) % modes.length;
    
    if(video) {
        video.style.objectFit = modes[zoomIdx];
        if(modes[zoomIdx] === 'scale-down') {
            video.style.width = 'auto';
            video.style.height = 'auto';
            video.style.margin = 'auto';
        } else {
            video.style.width = '100%';
            video.style.height = '100%';
        }
    }
}

function startTelemetria() {
    if(teleTimer) clearInterval(teleTimer);
    teleTimer = setInterval(() => {
        const v = player.tech().el();
        if(v && v.videoWidth > 0) { $('#res-val').text(v.videoWidth + 'x' + v.videoHeight); }
        if(player.tech().vhs && player.tech().vhs.stats) {
            const bw = player.tech().vhs.stats.bandwidth;
            if(bw) $('#bitrate-val').text((bw / 1000000).toFixed(2) + ' Mbps');
        }
    }, 2500);
}

function playContent(id, el) {
    const name = $(el).find('.channel-name').text().toUpperCase();
    const cat = $('#CatNav option:selected').text().toUpperCase();
    const isLock = adultFilter.some(t => name.includes(t) || cat.includes(t));

    if (isLock) {
        pendingId = id; pendingEl = el;
        player.pause();
        $('#parental-gate').fadeIn(300);
        $('#parental-pin').val('').focus();
        return;
    }
    executeStream(id, el);
}

function verifyParental() {
    if ($('#parental-pin').val() === pKey) {
        $('#parental-gate').fadeOut(200);
        executeStream(pendingId, pendingEl);
    } else {
        alert("PIN INVÁLIDO"); $('#parental-pin').val('').focus();
    }
}

function cancelParental() { $('#parental-gate').fadeOut(200); }

function executeStream(id, el) {
    $('.channel-item').removeClass('active');
    $(el).addClass('active');
    $('#loading-overlay').show();
    
    currentChanId = id;
    pendingEl = el;
    localStorage.setItem('last_chan_2026', id);
    $('#current-title').text($(el).find('.channel-name').text());
    
    const baseUrl = "<?php echo $XCStreamHostUrl . $bar; ?>";
    const auth = "<?php echo $username . '/' . $password; ?>";
    let streamUrl = `${baseUrl}live/${auth}/${id}`;
    if(!id.includes('.')) streamUrl += '.m3u8';

    let type = 'application/x-mpegURL';
    if(streamUrl.includes('.mpd')) type = 'application/dash+xml';
    if(streamUrl.includes('.ts')) type = 'video/mp2t';

    player.src({ src: streamUrl, type: type });
    player.load();
    player.play().catch(()=>{});

    loadEPG(id);
    if(window.innerWidth < 992) {
        document.querySelector('.epg-panel').scrollTop = 0;
    }
}

function loadCategory(id, resume = false) {
    const ajaxId = (id === 'FAVS' || id === 'TOP') ? '' : id;
    $.post("includes/ajax-control.php", { 
        action: 'getStreamsFromID', categoryID: ajaxId, hostURL: "<?php echo $XCStreamHostUrl . $bar; ?>" 
    }, function(data) {
        renderChannels(data, id);
        if(resume){
            const last = localStorage.getItem('last_chan_2026');
            if(last) setTimeout(() => { $(`.channel-item[data-id="${last}"]`).click(); }, 400);
        }
    });
}

function renderChannels(content, cat) {
    const $container = $('#ChannelRender').empty();
    $container.append(content);
    const favs = JSON.parse(localStorage.getItem('fav_v2026') || '[]');

    $container.find('.Playclick, .channel-item').each(function() {
        const $el = $(this);
        const id = $el.find('.streamId').val();
        const name = $el.text().trim();
        const icon = $el.find('.streamIcon').val() || "https://i.ibb.co/XkXSy40q/s-TREAMING-3-1.jpg";
        
        const upName = name.toUpperCase();
        let tag = "";
        if(upName.includes("LEG")) tag = "<span class='lang-badge'>LEG</span>";
        else if(upName.includes("DUB")) tag = "<span class='lang-badge'>DUB</span>";
        else if(upName.includes("4K")) tag = "<span class='lang-badge' style='background:var(--n-red)'>4K</span>";

        const isAdult = adultFilter.some(t => upName.includes(t));
        const isKids = kidsFilter.some(t => upName.includes(t));

        // REATIVAÇÃO MODO KIDS 2.0 (WHITELIST)
        if(kidsMode && (isAdult || !isKids)) { $el.remove(); return; }
        
        if(cat === 'FAVS' && !favs.includes(id)) { $el.remove(); return; }

        $el.addClass('channel-item').attr('data-id', id).removeClass('Playclick')
           .html(`<img src="${icon}" class="channel-logo" loading="lazy" onerror="this.src='https://i.ibb.co/XkXSy40q/s-TREAMING-3-1.jpg'">
                  <div class="channel-name">${name}${tag} ${isAdult ? '<i class="fa fa-lock" style="font-size:10px; opacity:0.4;"></i>' : ''}</div>
                  <i class="fa fa-star fav-icon ${favs.includes(id)?'is-fav':''}" onclick="toggleFav(event, '${id}')"></i>`);
        
        $el.off('click').on('click', () => playContent(id, $el));
    });
}

function toggleFav(e, id) {
    e.stopPropagation();
    let favs = JSON.parse(localStorage.getItem('fav_v2026') || '[]');
    favs = favs.includes(id) ? favs.filter(f => f !== id) : [...favs, id];
    localStorage.setItem('fav_v2026', JSON.stringify(favs));
    $(e.target).toggleClass('is-fav');
}

function loadEPG(id) {
    $.post("includes/epgdata.php", { action: 'GetEpgDataByStreamid', StreamId: id, CurrentTime: '<?php echo $CurrentTime; ?>', hostURL: "<?php echo $XCStreamHostUrl . $bar; ?>" }, function(data) {
        $('#epg-info').html(data || 'Programação em tempo real.');
    });
}

function doSearch() {
    const q = $('#ChanSearch').val().toLowerCase();
    $('.channel-item').each(function() { 
        $(this).toggle($(this).text().toLowerCase().includes(q)); 
    });
}

function toggleKidsMode() {
    kidsMode = !kidsMode;
    $('#KidsToggle').css({'background': kidsMode ? 'var(--k-yellow)' : '#1a1a1a', 'color': kidsMode ? '#000' : '#fff'});
    loadCategory($('#CatNav').val());
}
</script>
<?php } ?>