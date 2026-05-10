<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set("session.cookie_httponly", 1);
    ini_set("session.cookie_secure", 1);
    ini_set("session.cookie_samesite", "Strict");
    session_start();
}

$host = getenv("DB_HOST") ?: "db";
$db   = getenv("DB_NAME") ?: "pcmaster_db";
$user = getenv("DB_USER");
$pass = getenv("DB_PASS");

$dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    if (
        isset($_POST["elimina_immagine"]) ||
        isset($_POST["salva_principale"])
    ) {
        http_response_code(500);
        echo json_encode(["error" => $e->getMessage()]);
        exit();
    }
    die("Errore connessione: " . $e->getMessage());
}
?>
