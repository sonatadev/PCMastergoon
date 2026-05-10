<?php
include "db.php";

// 1. Controllo se l'ID è presente
if (!isset($_GET["id"])) {
    header("Location: catalogo.php");
    exit();
}

$prodotto_id = (int) $_GET["id"];

// 2. Recupero dati prodotto
$stmt = $pdo->prepare("SELECT * FROM Prodotto WHERE Prodotto_ID = ?");
$stmt->execute([$prodotto_id]);
$p = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$p) {
    die("Prodotto non trovato.");
}

// 3. Recupero TUTTE le immagini (Principale per prima)
$stmtImg = $pdo->prepare(
    "SELECT Nome_file, Principale FROM Immagini WHERE Prodotto_ID = ? ORDER BY Principale DESC",
);
$stmtImg->execute([$prodotto_id]);
$immagini = $stmtImg->fetchAll(PDO::FETCH_ASSOC);

$immagine_iniziale = !empty($immagini) ? $immagini[0]["Nome_file"] : "";

// 4. Gestione aggiunta al carrello
$messaggio = "";
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["aggiungi_carrello"])
) {
    if (!isset($_SESSION["utente_id"])) {
        header("Location: login.php");
        exit();
    }

    $u_id = $_SESSION["utente_id"];
    $qty_richiesta = (int) $_POST["quantita"];

    if ($qty_richiesta > $p["Quantita_magazzino"]) {
        $messaggio = "Errore: Disponibilità insufficiente.";
    } else {
        $stmtCheck = $pdo->prepare(
            "SELECT Quantita FROM Carrello WHERE Utente_ID = ? AND Prodotto_ID = ?",
        );
        $stmtCheck->execute([$u_id, $prodotto_id]);
        $esistente = $stmtCheck->fetch();

        if ($esistente) {
            $nuova_qty = $esistente["Quantita"] + $qty_richiesta;
            if ($nuova_qty > $p["Quantita_magazzino"]) {
                $messaggio =
                    "Superata la giacenza massima con i pezzi già nel carrello.";
            } else {
                $stmtUpd = $pdo->prepare(
                    "UPDATE Carrello SET Quantita = ? WHERE Utente_ID = ? AND Prodotto_ID = ?",
                );
                $stmtUpd->execute([$nuova_qty, $u_id, $prodotto_id]);
                header("Location: carrello.php");
                exit();
            }
        } else {
            $stmtIns = $pdo->prepare(
                "INSERT INTO Carrello (Utente_ID, Prodotto_ID, Quantita) VALUES (?, ?, ?)",
            );
            $stmtIns->execute([$u_id, $prodotto_id, $qty_richiesta]);
            header("Location: carrello.php");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(
        $p["Descrizione"],
    ); ?> - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/prodotto.css">
    <link rel="stylesheet" href="stylesheet/navbar.css">
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

    <main class="product-page">
        <div class="product-container">
            <div class="product-gallery">
                <div class="image-box">
                    <?php if ($immagine_iniziale): ?>
                        <img src="<?php echo $immagine_iniziale; ?>" id="main-img" alt="Prodotto">
                    <?php endif; ?>
                </div>

                <?php if (count($immagini) > 1): ?>
                    <div class="thumb-grid" style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                        <?php foreach ($immagini as $img): ?>
                            <img src="<?php echo htmlspecialchars(
                                $img["Nome_file"],
                            ); ?>" 
                                 class="thumb" 
                                 style="width: 70px; height: 70px; object-fit: cover; cursor: pointer; border: 2px solid #ddd; border-radius: 4px;"
                                 onclick="document.getElementById('main-img').src = this.src">
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="product-details">
                <span class="category-tag"><?php echo htmlspecialchars(
                    $p["Categoria"],
                ); ?></span>
                <h1><?php echo htmlspecialchars($p["Descrizione"]); ?></h1>
                
                <div class="price-tag"><?php echo number_format(
                    $p["Prezzo"],
                    2,
                    ",",
                    ".",
                ); ?>€</div>

                <div class="stock-status">
                    <?php if ($p["Quantita_magazzino"] > 0): ?>
                        <span class="status-in">● Disponibile (<?php echo $p[
                            "Quantita_magazzino"
                        ]; ?> pezzi)</span>
                    <?php else: ?>
                        <span class="status-out">● Esaurito</span>
                    <?php endif; ?>
                </div>

                <hr>

                <?php if ($messaggio): ?>
                    <div class="alert-error"><?php echo $messaggio; ?></div>
                <?php endif; ?>

                <?php if ($p["Quantita_magazzino"] > 0): ?>
                    <form method="POST" class="purchase-form">
                        <div class="qty-selector">
                            <label for="quantita">Quantità</label>
                            <select id="quantita" name="quantita" class="custom-select">
                                <?php
                                $max_tendina = min(
                                    $p["Quantita_magazzino"],
                                    10,
                                );
                                for ($i = 1; $i <= $max_tendina; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor;
                                ?>
                            </select>
                        </div>
                        <button type="submit" name="aggiungi_carrello" class="btn-add">
                            Aggiungi al Carrello
                        </button>
                    </form>
                <?php endif; ?>

                <div class="product-features">
                    <p>✓ Garanzia Italia 24 Mesi</p>
                    <p>✓ Spedizione Assicurata</p>
                </div>
            </div>
        </div>
    </main>
</body>
</html>