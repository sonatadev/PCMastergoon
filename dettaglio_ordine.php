<?php
include "db.php";

// 1. Controllo Sessione
if (!isset($_SESSION["utente_id"])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION["utente_id"];
$ruolo = $_SESSION["ruolo"];
$ordine_id = isset($_GET["id"]) ? (int) $_GET["id"] : 0;

if ($ordine_id <= 0) {
    die("Ordine non valido.");
}

// 2. Recupero info ordine e controllo sicurezza
$stmt = $pdo->prepare(
    "SELECT o.*, u.Username FROM Ordine o JOIN Utente u ON o.Utente_ID = u.Utente_ID WHERE o.Ordine_ID = ?",
);
$stmt->execute([$ordine_id]);
$ordine = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ordine) {
    die("Ordine non trovato.");
}

// SICUREZZA: Se non sei admin e l'ordine non è tuo, fuori!
if ($ruolo !== "admin" && $ordine["Utente_ID"] != $u_id) {
    die("Accesso negato: non puoi visualizzare gli ordini di altri utenti.");
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dettaglio Ordine - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link rel="stylesheet" href="stylesheet/navbar.css">
    <link rel="stylesheet" href="stylesheet/dettaglio_ordine.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

    <nav class="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-brand"><img src="img/logo mini no bg.png">PC Master</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="catalogo.php">Prodotti</a>
            
            <?php if (isset($_SESSION["utente_id"])): ?>
                <a href="carrello.php">Carrello</a>
                
                <a href="ordini.php">I miei Ordini</a>

                <?php if ($_SESSION["ruolo"] === "admin"): ?>
                    <a href="admin/area_admin.php">Area Admin</a>
                <?php endif; ?>

                <a href="logout.php" class="btn-logout">Logout (<?php echo htmlspecialchars(
                    $_SESSION["ruolo"],
                ); ?>)</a>
            <?php else: ?>
                <a href="login.php" class="btn-login">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

    <div class="admin-content">
        
        <h1>
            Dettaglio <?php echo $ruolo === "admin"
                ? "Ordine #$ordine_id"
                : "Acquisto"; ?>
        </h1>
        
        <div class="order-info-card">
            <p><strong>Data:</strong> <?php echo date(
                "d/m/Y H:i",
                strtotime($ordine["Data"]),
            ); ?></p>
            <p><strong>Metodo Pagamento:</strong> <?php echo htmlspecialchars(
                $ordine["Metodo_pagamento"],
            ); ?></p>
            <p><strong>Indirizzo Spedizione:</strong> <?php echo htmlspecialchars(
                $ordine["Indirizzo"],
            ); ?></p>
            
            <?php if ($ruolo === "admin"): ?>
                <p><strong>Cliente:</strong> <?php echo htmlspecialchars(
                    $ordine["Username"],
                ); ?> <span>(ID: <?php echo $ordine["Utente_ID"]; ?>)</span></p>
            <?php endif; ?>
        </div>

        <table class="order-table">
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th class="text-center">Quantità</th>
                    <th class="text-right">Prezzo Unitario</th>
                    <th class="text-right">Subtotale</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT od.QA_prodotto, p.Descrizione, p.Prezzo 
                        FROM Ordine_Dettaglio od 
                        JOIN Prodotto p ON od.Prodotto_ID = p.Prodotto_ID 
                        WHERE od.Ordine_ID = ?";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$ordine_id]);
                $prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $totale = 0;

                foreach ($prodotti as $p):

                    $sub = $p["Prezzo"] * $p["QA_prodotto"];
                    $totale += $sub;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars(
                            $p["Descrizione"],
                        ); ?></td>
                        <td class="text-center"><?php echo $p[
                            "QA_prodotto"
                        ]; ?></td>
                        <td class="text-right"><?php echo number_format(
                            $p["Prezzo"],
                            2,
                            ",",
                            ".",
                        ); ?>€</td>
                        <td class="text-right font-bold"><?php echo number_format(
                            $sub,
                            2,
                            ",",
                            ".",
                        ); ?>€</td>
                    </tr>
                <?php
                endforeach;
                ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <h3>Totale Ordine: <span class="total-price"><?php echo number_format(
                $totale,
                2,
                ",",
                ".",
            ); ?>€</span></h3>
        </div>

        <div class="actions">
            <a href="<?php echo $ruolo === "admin"
                ? "admin/area_admin.php"
                : "ordini.php"; ?>" class="btn-back">
                ← Torna alla lista
            </a>
        </div>
    </div>