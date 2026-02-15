<?php
/*
 * Versão aprimorada do código para maior clareza, manutenção e segurança.
 * Recomendado atualizar para PHP 7.4+ se possível.
 */

function renderBackground($activePage) {
    $bgImage = ($activePage == "dashboard") ? "images/dash_bg.jpg" : "images/login_bg.jpg";
    echo "<img class=\"body-bg\" src=\"$bgImage\" alt=\"\">";
}

// Renderiza o background condicional
renderBackground($activePage ?? 'dashboard'); // Adicionado fallback para evitar erro se undefined
?>

<div class="pattern-over"></div>

<div class="modal fade movie-popup" id="movieModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    </div>
</div>

<div class="modal fade movie-popup" id="accountModal1" tabindex="-1" role="dialog" aria-labelledby="AccountModal" aria-hidden="true" style="z-index: 999;">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="border:0;">
        <span class="p-close" data-dismiss="modal" aria-hidden="true">x</span>
      </div>
      <div class="modal-body">
        <div class="popup-content t-s">
          <div class="info_set clearfix" style="width:50%; margin: 5% auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.1);">
            <h1 class="text-center" style="color:#000;">Account Information</h1>
            <?php
            $user = $_SESSION["webTvplayer"] ?? null;
            if ($user):
            ?>
              <div class="row">
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Username</strong>
                  <p style="width:100%; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($user["username"]) ?>"><?= htmlspecialchars($user["username"]) ?></p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Status</strong>
                  <p><?= htmlspecialchars($user["status"]) ?></p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Expiry date</strong>
                  <p>
                    <?php
                    if (empty($user["exp_date"]) || $user["exp_date"] == "null") {
                        echo "Unlimited";
                    } else {
                        echo date("F d, Y", strtotime($user["exp_date"]));
                    }
                    ?>
                  </p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Is trial</strong>
                  <p><?= ($user["is_trial"] == "1") ? "Yes" : "No" ?></p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Active Connections</strong>
                  <p><?= htmlspecialchars($user["active_cons"]) ?></p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Created at</strong>
                  <p><?= date("F d, Y", strtotime($user["created_at"])) ?></p>
                </div>
                <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                  <strong>Max connections</strong>
                  <p><?= htmlspecialchars($user["max_connections"]) ?></p>
                </div>
              </div>
            <?php else: ?>
              <p>No account information available.</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div id="search" style="display:none;">
  <button type="button" class="close" onclick="$('#search').hide()">×</button>
  <input type="search" id="SearchData" placeholder="Type keyword(s) here" />
  <button type="submit" id="SearchBtn" class="btn btn-primary">Search</button>
</div>

<script src="js/offcanvas.js"></script>
<script src="js/bootstrap.js"></script>
<script src="js/classie.js"></script>
<script src="js/owl.carousel.min.js"></script>
<script src="js/plugin.js"></script>
<script src="js/jquery.infinitescroll.min.js"></script>
<script src="js/freewall.js"></script>
<script src="js/Manualcustom.js"></script>
<script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
<script src="js/jquery.rippler.min.js"></script>

<script>
$(document).ready(function() {
    // Animação ripple
    $(".rippler, li").rippler({
        effectClass: 'rippler-effect',
        effectSize: 0,
        addElement: 'div',
        duration: 400
    });

    // Exibir mensagem ao logout
    <?php if (isset($_GET["loggedout"])): ?>
        swal({title: 'Logged out!', text: 'You have been logged out successfully.', icon: 'success', buttons: false});
        setTimeout(function() { swal.close(); }, 2000);
    <?php endif; ?>

    // Abrir modal da conta
    $('#accountModal').click(function() {
        $('#accountModal1').modal('show');
    });

    // Menu lateral
    $('#cbp-spmenu-s1 li a').click(function() {
        $('#cbp-spmenu-s1 li a').removeClass('active');
        $(this).addClass('active');

        classie.toggle(showLeft, 'active');
        classie.toggle(menuLeft, 'cbp-spmenu-open');
        $('.cat-toggle').toggleClass('open');
    });

    // Mostrar/Hidrar elenco
    $(document).on('click', '.showCast', function() {
        $(this).parent('.i-cast').toggleClass('openCast');
        $(this).text() == 'Show Cast' ? $(this).text('Hide Cast') : $(this).text('Show Cast');
    });

    // Resetar player ao fechar modal
    $('#menuModal').on('hidden.bs.modal', function() {
        clearInt();
        $('#player-holder').html('');
        $('.backToInfo').addClass('hideOnLoad');
        $(document).find('.PlayerHolder div').html('').addClass('hideOnLoad');
        clearInt();
    });

    // Voltar à info do episódio
    $(document).on('click', '.backToInfo', function() {
        if ($('#player-holder').hasClass('flowplayer')) {
            var container = document.getElementById("player-holder");
            flowplayer(container).shutdown();
        }
        $('#player-holder').html('');
        $('#player-holder, .backToInfo').addClass('hideOnLoad');

        var episID = $(this).data('episid');
        if (episID) {
            $('#epis-' + episID).removeClass('hideOnLoad');
        } else {
            $('.poster-details').removeClass('hideOnLoad');
        }
        clearInt();
    });

    // Atualiza hora
    setInterval(function() {
        var date = new Date();
        var hours = date.getHours();
        var minutes = date.getMinutes();
        var ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        minutes = minutes < 10 ? '0' + minutes : minutes;
        var strTime = hours + ':' + minutes + ' ' + ampm;
        $('.time').html(strTime);
    }, 1000);

    // Grids
    $(".free-wall").each(function() {
        var wall = new Freewall(this);
        wall.reset({
            selector: '.thumb-b',
            animate: true,
            cellW: 185,
            cellH: 278,
            fixSize: 0,
            gutterY: 0,
            gutterX: -15,
            onResize: function() { wall.fitWidth(); }
        });
        wall.fitWidth();
    });
});
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function startAntiKickSystem() {
        // Pega o ID da sessão
        const sessionId  = "<?= session_id() ?>"; 
        
        // Tenta pegar o Host de várias formas possíveis para garantir que não venha vazio
        const currentDns = "<?= $_SESSION['dns_host'] ?? $_SESSION['webTvplayer']['server_url'] ?? $_SESSION['user_dns_host'] ?? '' ?>"; 

        // SEGURANÇA: Se não tiver sessão ou DNS (ex: usuário deslogou), para o script para evitar erros
        if(!sessionId || !currentDns || currentDns === '') return;

        console.log("Monitoramento Anti-Kick Ativo...");

        // Verifica a cada 10 segundos
        setInterval(function() {
            $.post('api/dns.php', {
                action: 'check_integrity',
                sess_id: sessionId,
                host: currentDns
            }, function(response) {
                // Se a API responder 'kicked', desloga
                if (response && response.status === 'kicked') {
                    window.location.href = 'logout.php?reason=kicked'; 
                }
            }, 'json').fail(function() {
                // Se a internet cair ou a API der erro, não faz nada (evita kick falso)
                console.log("Falha de conexão com o servidor de verificação.");
            });
        }, 10000); 
    }

    $(document).ready(function() {
        startAntiKickSystem();
    });
</script>

</body>
</html>