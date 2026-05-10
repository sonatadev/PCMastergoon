<?php
include "db.php";

// Controllo sessione (db.php dovrebbe già avere session_start(), altrimenti aggiungilo lì)
if (!isset($_SESSION["utente_id"])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION["utente_id"];
$messaggio = "";

// 1. Recupero i prodotti nel carrello
$stmt = $pdo->prepare("SELECT c.Quantita, p.Prodotto_ID, p.Descrizione, p.Prezzo, p.Quantita_magazzino 
                       FROM Carrello c 
                       JOIN Prodotto p ON c.Prodotto_ID = p.Prodotto_ID 
                       WHERE c.Utente_ID = ?");
$stmt->execute([$u_id]);
$carrello = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Se il carrello è vuoto e non siamo nella pagina di successo, torna al catalogo
if (empty($carrello) && !isset($_GET["successo"])) {
    header("Location: catalogo.php");
    exit();
}

// 2. Gestione della conferma d'ordine
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["conferma_acquisto"])
) {
    $indirizzo = $_POST["indirizzo"];
    $metodo_pagamento = $_POST["metodo_pagamento"];

    try {
        $pdo->beginTransaction();

        // Inserimento Ordine principale
        $stmtOrdine = $pdo->prepare(
            "INSERT INTO Ordine (Utente_ID, Data, Indirizzo, Metodo_pagamento) VALUES (?, NOW(), ?, ?)",
        );
        $stmtOrdine->execute([$u_id, $indirizzo, $metodo_pagamento]);
        $ordine_id = $pdo->lastInsertId();

        foreach ($carrello as $item) {
            // Controllo disponibilità dell'ultimo secondo
            if ($item["Quantita"] > $item["Quantita_magazzino"]) {
                throw new Exception(
                    "Errore: Il prodotto " .
                        $item["Descrizione"] .
                        " non è più disponibile a sufficienza.",
                );
            }

            // Inserimento dettaglio ordine
            $stmtDettaglio = $pdo->prepare(
                "INSERT INTO Ordine_Dettaglio (Ordine_ID, Prodotto_ID, QA_prodotto) VALUES (?, ?, ?)",
            );
            $stmtDettaglio->execute([
                $ordine_id,
                $item["Prodotto_ID"],
                $item["Quantita"],
            ]);

            // Scarico magazzino
            $stmtUpdateMagazzino = $pdo->prepare(
                "UPDATE Prodotto SET Quantita_magazzino = Quantita_magazzino - ? WHERE Prodotto_ID = ?",
            );
            $stmtUpdateMagazzino->execute([
                $item["Quantita"],
                $item["Prodotto_ID"],
            ]);
        }

        // Svuota carrello dell'utente
        $stmtSvuota = $pdo->prepare("DELETE FROM Carrello WHERE Utente_ID = ?");
        $stmtSvuota->execute([$u_id]);

        $pdo->commit();
        header("Location: checkout.php?successo=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $messaggio = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link rel="stylesheet" href="stylesheet/navbar.css">
    <link rel="stylesheet" href="stylesheet/checkout.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="nav-brand"><img src="img/logo mini no bg.png">PC Master</a>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="catalogo.php">Prodotti</a>
                
                <?php if (isset($_SESSION["utente_id"])): ?>
                    <?php if (
                        $_SESSION["ruolo"] === "cliente" ||
                        $_SESSION["ruolo"] === "admin"
                    ): ?>
                        <a href="carrello.php">Carrello</a>
                        <?php if ($_SESSION["ruolo"] === "admin"): ?>
                            <a href="admin/area_admin.php">Area Admin</a>
                        <?php endif; ?>
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

    <div class="checkout-container">
        <?php if (isset($_GET["successo"])): ?>
            <div class="success" style="text-align: center; padding: 50px;">
                <h1 style="color: #27ae60; font-size: 3rem;">✔️</h1>
                <h1>Grazie per l'acquisto!</h1>
                <p>Il tuo ordine è stato ricevuto correttamente ed è in fase di elaborazione.</p>
                <a href="catalogo.php" class="btn-buy" style="text-decoration:none; display:inline-block; width:auto; margin-top:20px;">Torna allo shopping</a>
            </div>
        <?php else: ?>
            <h1>Completa l'Ordine</h1>
            
            <?php if ($messaggio): ?>
                <div class="error-msg" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <?php echo $messaggio; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Indirizzo di Spedizione</label>
                    <input type="text" name="indirizzo" placeholder="Via, Città, CAP" required style="width: 100%; padding: 12px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd;">
                </div>
                
                <div class="form-group" style="margin-top: 20px;">
                    <label>Metodo di Pagamento</label>
                    <select name="metodo_pagamento" required style="width: 100%; padding: 12px; margin-top: 5px; border-radius: 6px; border: 1px solid #ddd;">
                        <option value="Carta di Credito">Carta di Credito</option>
                        <option value="PayPal">PayPal</option>
                        <option value="Bonifico">Bonifico Bancario</option>
                    </select>
                </div>

                <div class="summary-box" style="background: #f9f9f9; padding: 20px; border-radius: 10px; margin-top: 30px;">
                    <h3>Riepilogo Prodotti</h3>
                    <ul class="checkout-list" style="list-style: none; padding: 0;">
                        <?php
                        $totale = 0;
                        foreach ($carrello as $item):

                            $subtotale = $item["Prezzo"] * $item["Quantita"];
                            $totale += $subtotale;
                            ?>
                            <li style="display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee;">
                                <span><?php echo htmlspecialchars(
                                    $item["Descrizione"],
                                ); ?> <strong>(x<?php echo $item[
     "Quantita"
 ]; ?>)</strong></span>
                                <span><?php echo number_format(
                                    $subtotale,
                                    2,
                                    ",",
                                    ".",
                                ); ?>€</span>
                            </li>
                        <?php
                        endforeach;
                        ?>
                    </ul>
                    
                    <div class="total-section" style="display: flex; justify-content: space-between; margin-top: 20px; font-size: 1.5rem; font-weight: 800;">
                        <span>Totale:</span>
                        <span style="color: #007bff;"><?php echo number_format(
                            $totale,
                            2,
                            ",",
                            ".",
                        ); ?>€</span>
                    </div>
                </div>

                <button type="submit" name="conferma_acquisto" class="btn-buy" style="margin-top: 30px; cursor: pointer;">
                    Conferma e Paga
                </button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>