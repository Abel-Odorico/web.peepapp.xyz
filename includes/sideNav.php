<?php
/*
 * DASHBOARD - PRIME VIDEO CLONE (PIXEL-PERFECT EDITION)
 * Identidade Visual: 100% Fiel (Cores, Fontes e Efeitos Oficiais)
 * Funcionalidade: 100% Preservada
 */

// ====================================================================
// 1. ESTILOS CSS "PRIME ORIGINAL"
// ====================================================================
echo "
<!-- Fonte mais próxima da Amazon Ember -->
<link href=\"https://fonts.googleapis.com/css2?family=PT+Sans:wght@400;700&display=swap\" rel=\"stylesheet\">

<style>
    /* --- VARIÁVEIS OFICIAIS --- */
    :root {
        --prime-bg-deep: #0f171e;      /* Fundo Principal */
        --prime-bg-card: #1b2530;      /* Cards e Nav */
        --prime-blue: #00A8E1;         /* Ação Principal */
        --prime-hover: #252e39;        /* Estado Hover */
        --text-active: #ffffff;        /* Branco Puro */
        --text-inactive: #8197a4;      /* Cinza Azulado */
    }

    /* Reset Geral para garantir a fonte */
    body, .navbar, .modal-content, input, button, h1, h2, h3, a {
        font-family: 'PT Sans', sans-serif !important;
    }

    /* --- NAVBAR SUPERIOR --- */
    .prime-navbar.navbar-inverse {
        background-color: var(--prime-bg-card) !important;
        border: none !important;
        min-height: 70px; /* Altura padrão TV */
        margin-bottom: 0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.4);
        z-index: 1000;
    }
    
    .full-container { padding: 0 40px; } /* Espaçamento lateral wide */

    /* LINKS DE NAVEGAÇÃO (ESTILO ABAS) */
    .prime-nav-links { margin-left: 20px; }
    
    .prime-nav-links > li > a {
        color: var(--text-inactive) !important;
        font-weight: 700; /* Amazon usa Bold nos menus */
        font-size: 15px;
        padding: 24px 15px !important; /* Altura para centralizar na barra de 70px */
        background: transparent !important;
        text-transform: capitalize;
        border-bottom: 2px solid transparent; /* Prepara para o hover */
        transition: all 0.2s ease-in-out;
    }

    /* Estado Ativo e Hover */
    .prime-nav-links > li.active > a {
        color: var(--text-active) !important;
        border-bottom: 2px solid var(--text-active); /* A linha branca clássica */
    }
    
    .prime-nav-links > li > a:hover:not(.active) {
        color: var(--text-active) !important;
    }

    /* --- CAMPO DE BUSCA (ESTILO INPUT DARK) --- */
    .master-search-container {
        position: relative;
        display: flex;
        align-items: center;
        background: #000; /* Fundo preto do input */
        border: 1px solid #425265; /* Borda cinza sutil */
        border-radius: 2px; /* Cantos levemente quadrados */
        padding: 6px 10px;
        margin-top: 15px;
        width: 260px;
        transition: all 0.2s;
    }
    
    .master-search-container:focus-within {
        border-color: var(--prime-blue);
        box-shadow: 0 0 0 1px var(--prime-blue); /* Glow Sólido */
    }

    .master-search-container input {
        background: transparent; border: none; color: #fff;
        width: 100%; outline: none; font-size: 15px; font-weight: 400;
        margin-left: 8px;
    }
    
    .master-search-container ::placeholder { color: var(--text-inactive); opacity: 0.7; }
    .search-icon { color: var(--text-inactive); font-size: 16px; }

    /* --- ÍCONES E AÇÕES --- */
    .r-icon .r-li a {
        color: var(--text-inactive) !important;
        transition: color 0.2s;
    }
    .r-icon .r-li a:hover { color: var(--text-active) !important; }

    /* --- MENU LATERAL (SLIDE) --- */
    .cbp-spmenu {
        background: var(--prime-bg-deep) !important;
        border-right: 1px solid #2a3b4c;
        box-shadow: 2px 0 10px rgba(0,0,0,0.5);
    }
    .cbp-spmenu h3 {
        background: var(--prime-bg-card) !important;
        color: var(--prime-blue) !important;
        font-weight: 700;
        border-bottom: 1px solid #2a3b4c;
    }
    .cbp-spmenu a {
        color: #ccc !important;
        font-weight: 400;
        border-bottom: 1px solid #1f2a36 !important;
    }
    .cbp-spmenu a:hover, .cbp-spmenu a.active {
        background: var(--prime-bg-card) !important;
        color: var(--prime-blue) !important;
        font-weight: 700;
        border-left: 3px solid var(--prime-blue);
    }

    /* --- MODAL (POPUP) --- */
    .modal-content {
        background-color: var(--prime-bg-card) !important;
        color: #fff !important;
        border: 1px solid #333;
        border-radius: 4px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.7);
    }
    .modal-header { border-bottom: 1px solid rgba(255,255,255,0.1) !important; padding: 20px; }
    .modal-footer { border-top: 1px solid rgba(255,255,255,0.1) !important; }
    .modal-title { font-weight: 700; font-size: 18px; }
    .close { color: #fff !important; opacity: 0.7; text-shadow: none; font-weight: 300; font-size: 30px; }
    
    /* BOTÕES */
    .btn-primary { 
        background-color: var(--prime-blue) !important; 
        border: none; 
        border-radius: 3px;
        font-weight: 700; 
        text-transform: uppercase; 
        font-size: 13px;
        padding: 10px 20px;
        letter-spacing: 0.5px;
    }
    .btn-primary:hover { background-color: #0082af !important; }
    
    .btn-default { 
        background: transparent !important; 
        border: 1px solid #8197a4 !important; 
        color: #fff !important; 
        border-radius: 3px;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 13px;
    }
    .btn-default:hover { background: rgba(255,255,255,0.1) !important; }

    /* RADIO BUTTONS CUSTOMIZADOS (MODAL) */
    .custom-radio { margin-bottom: 10px; cursor: pointer; display: flex; align-items: center; }
    .custom-radio input { display: none; }
    .radio-dot {
        height: 18px; width: 18px; 
        border: 2px solid var(--text-inactive); border-radius: 50%; 
        display: inline-block; margin-right: 10px; position: relative;
    }
    .custom-radio input:checked + .radio-dot { border-color: var(--prime-blue); }
    .custom-radio input:checked + .radio-dot::after {
        content: ''; position: absolute; top: 3px; left: 3px;
        width: 8px; height: 8px; background: var(--prime-blue); border-radius: 50%;
    }
    .custom-radio span { color: #ccc; font-weight: 400; }
    .custom-radio input:checked ~ span { color: #fff; font-weight: 700; }

    .master-hide { display: none !important; }
</style>
";

// ====================================================================
// 2. LÓGICA PHP INTACTA
// ====================================================================

// Seção de Categorias Lateral
if ($activePage !== "dashboard" && $activePage !== "settings") {
    echo "
    <nav class=\"cbp-spmenu cbp-spmenu-vertical cbp-spmenu-left\" id=\"cbp-spmenu-s1\">
        <h3>Navegar</h3>
        <ul>";
    if (!empty($FinalCategoriesArray) && $FinalCategoriesArray["result"] == "success") {
        $ConditionCounter = 1;
        foreach ($FinalCategoriesArray["data"] as $catkey) {
            $OnloadActiveclass = ($ConditionCounter == 1) ? "active onloadCallCategory" : "";
            $ConditionCounter += 1;
            $clickAction = (webtvpanel_parentcondition($catkey->category_name) == 1) ? "confirmparent('{$catkey->category_id}')" : "getData('{$catkey->category_id}')";
            
            echo "<li>
                <a onclick=\"{$clickAction}\" data-CategoryID=\"{$catkey->category_id}\" data-pcon=\"" . webtvpanel_parentcondition($catkey->category_name) . "\" class=\"{$OnloadActiveclass}\">
                    " . ($catkey->category_name != "other channels" ? $catkey->category_name : "Diversos") . "
                </a>
            </li>";
        }
    }
    echo "</ul></nav>";
}

// Início da Navbar HTML
echo "<nav class=\"navbar navbar-inverse navbar-static-top prime-navbar\">
    <div class=\"container full-container navb-fixid\">";

if ($activePage !== "dashboard" && $activePage !== "settings") {
    echo "
        <div class=\"navbar-header\">
            <div id=\"showLeft\" class=\"cat-toggle\"> 
                <i class=\"fa fa-bars\" style=\"color: #fff; font-size: 20px; margin-top: 25px; cursor: pointer;\"></i>
            </div>
            <button type=\"button\" class=\"navbar-toggle collapsed pull-right\" data-toggle=\"offcanvas\"> 
                <span class=\"icon-bar\"></span> <span class=\"icon-bar\"></span> <span class=\"icon-bar\"></span> 
            </button>
        </div>";
}

// Logo e Links Principais
echo "
        <a class=\"brand\" href=\"dashboard.php\" style=\"float:left; margin-right: 40px;\">
            <img src=\"" . (isset($XClogoLinkval) && !empty($XClogoLinkval) ? $XClogoLinkval : "img/logo.png") . "\" alt=\"Logo\" onerror=\"this.src='img/logo.png';\" style=\"height: 28px; margin-top: 21px; filter: brightness(0) invert(1);\">
        </a>

        <div id=\"navbar\" class=\"collapse navbar-collapse sidebar-offcanvas\">
            <ul class=\"nav navbar-nav navbar-left main-icon prime-nav-links\">
                <li class=\"" . ($activePage == "dashboard" ? "active" : "") . "\"><a href=\"dashboard.php\">Início</a></li>
                <li class=\"" . ($activePage == "live" ? "active" : "") . "\"><a href=\"live.php\">Canais</a></li>
                <li class=\"" . ($activePage == "movies" ? "active" : "") . "\"><a href=\"movies.php\">Filmes</a></li>
                <li class=\"" . ($activePage == "series" ? "active" : "") . "\"><a href=\"series.php\">Séries</a></li>
            </ul>

            <ul class=\"nav navbar-nav navbar-right r-icon\">";
            
            // Busca e Filtros
            if ($activePage !== "dashboard" && $activePage !== "settings") {
                echo "
                <li class=\"r-li master-search-item\">
                    <div class=\"master-search-container\">
                        <i class=\"fa fa-search search-icon\"></i>
                        <input type=\"text\" id=\"MasterSearch\" placeholder=\"Busca\" onkeyup=\"runMasterSearch()\">
                        <i class=\"fa fa-times clear-icon\" onclick=\"clearMasterSearch()\" id=\"clearBtn\" style=\"display:none; color:#888; cursor:pointer; margin-left:5px;\"></i>
                    </div>
                </li>
                <li class=\"r-li\"><a href=\"#sort\" data-toggle=\"modal\" data-target=\"#sortingpopup\" style=\"margin-top: 15px;\"><i class=\"fa fa-sort-amount-desc\" style=\"font-size:18px;\"></i></a></li>";
            }

            echo "
                <li class=\"r-li " . ($activePage == "settings" ? "active" : "") . "\">
                    <a href=\"settings.php\" style=\"margin-top: 15px;\"><i class=\"fa fa-cog\" style=\"font-size:18px;\"></i></a>
                </li>
                <li class=\"r-li\">
                    <a href=\"logout.php\" class=\"logoutBtn\" style=\"margin-top: 15px;\">
                        <i class=\"fa fa-sign-out\" style=\"font-size:18px;\"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>";
?>

<script>
// Mantendo a lógica JS original
function runMasterSearch() {
    var input = document.getElementById('MasterSearch');
    var filter = input.value.toLowerCase();
    var clearBtn = document.getElementById('clearBtn');
    
    clearBtn.style.display = (filter.length > 0) ? 'block' : 'none';

    var items = document.querySelectorAll('.thumb-b, .streamList, .sectionNo');
    var foundCount = 0;

    for (var i = 0; i < items.length; i++) {
        var textContent = items[i].textContent || items[i].innerText;
        if (textContent.toLowerCase().indexOf(filter) > -1) {
            items[i].classList.remove('master-hide');
            foundCount++;
        } else {
            items[i].classList.add('master-hide');
        }
    }

    var noResult = document.getElementById('NoResultFound');
    if (noResult) {
        (foundCount === 0 && filter !== '') ? noResult.classList.remove('hideOnLoad') : noResult.classList.add('hideOnLoad');
    }

    if (window.wall) window.wall.fitWidth();
    if (window.$) $(window).trigger('resize');
}

function clearMasterSearch() {
    document.getElementById('MasterSearch').value = '';
    runMasterSearch();
}
</script>

<!-- MODAL DE ORDENAÇÃO (REESTILIZADO) -->
<div class="modal fade" id="sortingpopup" role="dialog" style="background: rgba(0, 0, 0, 0.9)">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Ordenar por</h4>
            </div>
            <div class="modal-body" style="padding: 20px 30px;">
                <div class="sorting-container">
                    <?php
                    $sortCondition = isset($_COOKIE[$SessioStoredUsername . "_" . $activePage]) ? $_COOKIE[$SessioStoredUsername . "_" . $activePage] : "default";
                    $options = [
                        'default' => 'Recomendados', 
                        'topadded' => 'Adicionados Recentemente', 
                        'asc' => 'Alfabético (A-Z)', 
                        'desc' => 'Alfabético (Z-A)'
                    ];
                    foreach ($options as $val => $label) {
                        $checked = ($sortCondition == $val) ? "checked" : "";
                        echo "
                        <label class='custom-radio'>
                            <input type='radio' name='sorttype' class='sorttype' value='$val' $checked>
                            <span class='radio-dot'></span>
                            <span>$label</span>
                        </label>";
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                <button type="button" id="savesorting" data-sortin="<?php echo $activePage; ?>" class="btn btn-primary">Salvar Alterações</button>
            </div>
        </div>
    </div>
</div>
