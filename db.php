<?php
if (session_status() === PHP_SESSION_NONE) {
    // Cookie sicuri — devono essere impostati PRIMA di session_start()
    ini_set("session.cookie_httponly", 1); // JS non può leggere il cookie
    ini_set("session.cookie_secure", 1); // Solo HTTPS
    ini_set("session.cookie_samesite", "Strict"); // Protegge da CSRF
    session_start();
}

$host = "db";
$db = "pcmaster_db";
$user = "goon_user";
$pass = "goon_password";
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
