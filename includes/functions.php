<?php
/*
 * @ PHP 5.6
 * @ Decoder version : 1.0.0.1
 */

$FileNameExtension = basename(strtok($_SERVER["REQUEST_URI"], "?"));
$fileName = explode("/", $_SERVER["SCRIPT_FILENAME"]);
$activePage = str_replace(".php", "", end($fileName));
$streamData = "";
$configFileCheck = webtvpanel_checkfilepermission("configuration.php");

// Função de ordenação por data
function webtvpanel_date_sort($a, $b)
{
    $time1 = strtotime($a);
    $time2 = strtotime($b);
    if ($time1 < $time2) {
        return 1;
    } elseif ($time1 > $time2) {
        return -1;
    } else {
        return 0;
    }
}

// Requisição API com cURL
function webtvpanel_CallApiRequest($ApiLinkIs = "")
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $ApiLinkIs);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $response = curl_exec($ch);
    if ($response === false) {
        return ["result" => "error", "data" => "Invalid Host Url"];
    }

    $Result = json_decode($response);
    if (!empty($Result)) {
        return ["result" => "success", "data" => $Result];
    }
    return ["result" => "error"];
}

// Verifica permissão do arquivo
function webtvpanel_checkFilePermission($fileName = "")
{
    if (file_exists($fileName)) {
        $Permission = substr(sprintf("%o", fileperms($fileName)), -4);
        if (in_array($Permission, ["0644", "0755", "0777"])) {
            return ["result" => "success", "permission" => $Permission];
        }
        return ["result" => "error"];
    }
    return ["result" => "error"];
}

// Verifica se o usuário está autorizado no stream
function webtvpanel_CheckstreamLine($username = "", $password = "", $hostURL = "")
{
    $returnData = "0";
    $bar = (substr($hostURL, -1) == "/") ? "" : "/";
    $Servername = $hostURL . $bar;
    $ApiLinkIs = $Servername . "player_api.php?username=" . $username . "&password=" . $password;

    $CallApi = webtvpanel_CallApiRequest($ApiLinkIs);
    if (!empty($CallApi) && $CallApi["result"] == "success") {
        if (isset($CallApi["data"]->user_info->auth) && $CallApi["data"]->user_info->auth != 0 && $CallApi["data"]->user_info->status == "Active") {
            $returnData = "1";
        }
    } else {
        $returnData = "0";
    }
    return $returnData;
}


// Obter categorias logadas
function webtvpanel_getLoggedInCategories()
{
    // Função vazia, pode implementar lógica específica aqui
}

// Obter link de vídeo ao vivo
function getLiveVideoLink($streamID = "", $streamType = "")
{
    // Função vazia, implementar se necessário
}

// Avaliação de estrelas
function webtvpanel_starRating($rating = "")
{
    if (is_float($rating)) {
        $fullStars = intval($rating);
        $halfStar = ($rating - $fullStars) >= 0.5;
        for ($i = 0; $i < $fullStars; $i++) {
            echo "<span class=\"fa fa-star\"></span>";
        }
        if ($halfStar) {
            echo "<span class=\"fa fa-star-half\"></span>";
        }
        for ($i = $fullStars + ($halfStar ? 1 : 0); $i < 5; $i++) {
            echo "<span class=\"fa fa-star-o\"></span>";
        }
    } else {
        $ratingInt = intval($rating);
        for ($i = 0; $i < $ratingInt; $i++) {
            echo "<span class=\"fa fa-star\"></span>";
        }
        for ($i = $ratingInt; $i < 5; $i++) {
            echo "<span class=\"fa fa-star-o\"></span>";
        }
    }
}

// Verifica configurações de player
function webtvpanel_checkPlayer()
{
    if (isset($_COOKIE["settings_array"]) && !empty($_COOKIE["settings_array"])) {
        return json_decode($_COOKIE["settings_array"]);
    }
    return null;
}

// Encode base64
function webtvpanel_baseEncode($Text = "")
{
    return $Text !== "" ? base64_encode($Text) : "";
}

// Decode base64
function webtvpanel_baseDecode($Text = "")
{
    return $Text !== "" ? base64_decode($Text) : "";
}

// Condição parental
function webtvpanel_parentcondition($Text = "")
{
    $parentenable = "";
    $parentpassword = "";
    $result = 0;

    if (isset($_COOKIE["settings_array"])) {
        $SessionUser = $_SESSION["webTvplayer"]["username"];
        $SettingArray = json_decode($_COOKIE["settings_array"]);
        if (isset($SettingArray->{$SessionUser}) && !empty($SettingArray->{$SessionUser})) {
            $parentenable = $SettingArray->{$SessionUser}->parentenable;
            $parentpassword = $SettingArray->{$SessionUser}->parentpassword;
        }
    }

    if ($parentenable == "on") {
        $patterns = ["%adults%", "%adult%", "%Adults%", "%XXX%", "%Porn%", "%xxx%", "%Sexy%", "%foradults%", "%ADULTE%", "%adulte%"];
        foreach ($patterns as $pattern) {
            if (webtvpanel_like_match($pattern, $Text)) {
                $result = 1;
                break;
            }
        }
    }
    return $result;
}

// Padrão de correspondência LIKE
function webtvpanel_like_match($pattern, $subject)
{
    $regexPattern = str_replace("%", ".*", preg_quote($pattern, "/"));
    return preg_match("/^" . $regexPattern . "$/i", $subject) === 1;
}

// Verificar DNS permitido
function webtv_check_dns_allowed($m3uLink = null)
{
    try {
        $dbPath = __DIR__ . '/../admin/api/.db.db';
        $db = new SQLite3($dbPath, SQLITE3_OPEN_READONLY);
        $db->busyTimeout(5000);
        $host = '';

        if ($m3uLink) {
            $url = parse_url($m3uLink);
            if (!isset($url['host'])) return false;
            $host = $url['host'];
        } else {
            $host = $_SERVER['HTTP_HOST'];
        }

        $stmt = $db->prepare("SELECT 1 FROM dns WHERE url LIKE :url LIMIT 1");
        $stmt->bindValue(':url', '%' . $host . '%', SQLITE3_TEXT);
        $res = $stmt->execute();

        $allowed = $res->fetchArray() ? true : false;
        $db->close();
        return $allowed;
    } catch (Throwable $e) {
        return false;
    }
}

// Parseia arquivo M3U8
function webtv_parse_m3u8($m3u)
{
    $result = ['server' => null, 'username' => null, 'password' => null];

    if (strpos($m3u, 'get.php') !== false) {
        $url = parse_url($m3u);
        parse_str($url['query'] ?? '', $q);
        $result['server'] = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');
        $result['username'] = $q['username'] ?? null;
        $result['password'] = $q['password'] ?? null;
    } elseif (preg_match('#^(https?://[^/]+)/live/([^/]+)/([^/]+)/#', $m3u, $matches)) {
        $result['server'] = $matches[1];
        $result['username'] = $matches[2];
        $result['password'] = $matches[3];
    }

    return $result;
}

// LOGIN VIA LINK M3U8
if (strpos($_POST['uname'], 'http') === 0) {
    $parsed = webtv_parse_m3u8($_POST['uname']);
    if (!$parsed['server'] || !$parsed['username'] || !$parsed['password']) {
        echo json_encode(["result" => "error", "message" => "Link M3U8 inválido"]);
        exit;
    }
    $_POST['server'] = $parsed['server'];
    $_POST['uname'] = $parsed['username'];
    $_POST['upass'] = $parsed['password'];
}

// Sistema de login (m3u8 + manual)
if (isset($_POST["action"]) && $_POST["action"] == "webtvlogin") {
    $server = $_POST["server"];
    $UserName = $_POST["uname"];
    $UserPassword = $_POST["upass"];
    $rememberMe = $_POST["rememberMe"];

    // Verifica se o link já contém usuário e senha
    $inputCheck = (strpos($server, 'http') !== false) ? $server : $UserName;
    if (strpos($inputCheck, 'username=') !== false && strpos($inputCheck, 'password=') !== false) {
        $urlParts = parse_url($inputCheck);
        parse_str($urlParts['query'], $query);
        $UserName = $query['username'];
        $UserPassword = $query['password'];
        $server = $urlParts['scheme'] . "://" . $urlParts['host'] . (isset($urlParts['port']) ? ":" . $urlParts['port'] : "");
    }

    $_SESSION["server"] = $server;
    $ApiLinkIs = $server . "/player_api.php?username=" . $UserName . "&password=" . $UserPassword;
    $checkLogin = webtvpanel_CallApiRequest($ApiLinkIs);

    if ($checkLogin["result"] == "success") {
        $data = $checkLogin["data"];
        if (isset($data->user_info->auth) && $data->user_info->auth != 0 && $data->user_info->status == "Active") {
            if ($rememberMe == "on") {
                setcookie("username", $UserName, time() + 1209600, "/", $_SERVER["SERVER_NAME"]);
                setcookie("userpassword", base64_encode($UserPassword), time() + 1209600, "/", $_SERVER["SERVER_NAME"]);
            }
            $_SESSION["webTvplayer"] = [
                "username" => $data->user_info->username,
                "password" => $data->user_info->password,
                "auth" => $data->user_info->auth,
                "status" => $data->user_info->status,
                "exp_date" => $data->user_info->exp_date,
                "url" => $data->server_info->url,
                "port" => $data->server_info->port,
                "timezone" => $data->server_info->timezone
            ];
            echo json_encode(["result" => "success", "message" => $_SESSION["webTvplayer"]]);
        } else {
            echo json_encode(["result" => "error", "message" => "Conta Inativa ou Inválida"]);
        }
    } else {
        echo json_encode(["result" => "error", "message" => "Erro na conexão com o servidor"]);
    }
    exit;
}
?>