<?php
// test_db.php
$dbPath = __DIR__ . '/api/.db.db';
if (!file_exists($dbPath)) { die("ERRO: Banco de dados não encontrado em $dbPath"); }

$db = new SQLite3($dbPath);
$res = $db->query("SELECT * FROM dns");

echo "<h3>DNS Cadastradas no Painel:</h3>";
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    echo "ID: " . $row['id'] . " | Título: " . $row['title'] . " | URL: " . $row['url'] . "<br>";
}
?>