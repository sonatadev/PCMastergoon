<?php
include "db.php";

if (!isset($_SESSION["utente_id"])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION["utente_id"];

// RIMOZIONE PRODOTTO
if (isset($_GET["rimuovi"])) {
    $p_id = $_GET["rimuovi"];
    $stmtDel = $pdo->prepare(
        "DELETE FROM Carrello WHERE Utente_ID = ? AND Prodotto_ID = ?",
    );
    $stmtDel->execute([$u_id, $p_id]);
    header("Location: carrello.php");
    exit();
}

// RECUPERO PRODOTTI (Query corretta)
$query = "SELECT c.Quantita, p.Prodotto_ID, p.Descrizione, p.Prezzo, i.Nome_file 
          FROM Carrello c 
          JOIN Prodotto p ON c.Prodotto_ID = p.Prodotto_ID 
          LEFT JOIN Immagini i ON p.Prodotto_ID = i.Prodotto_ID AND i.Principale = 1
          WHERE c.Utente_ID = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$u_id]);
$carrello = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totale = 0;
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Il Mio Carrello - PC Master</title>
    <link rel="icon"  href="img/logo mini no bg.png">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="stylesheet/carrello.css">
    <link rel="stylesheet" href="stylesheet/navbar.css">
</head>
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

    <main class="container">
        <h1>Il Tuo Carrello</h1>

        <?php if (count($carrello) > 0): ?>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Prodotto</th>
                            <th>Descrizione</th>
                            <th>Quantità</th>
                            <th>Prezzo</th>
                            <th>Subtotale</th>
                            <th>Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($carrello as $item):

                            $subtotale = $item["Prezzo"] * $item["Quantita"];
                            $totale += $subtotale;
                            ?>
                            <tr>
                                <td>
                                    <div class="img-wrapper">
                                        <img src="<?php echo $item["Nome_file"]
                                            ? $item["Nome_file"]
                                            : "img/placeholder.png"; ?>" alt="Prodotto">
                                    </div>
                                </td>
                                <td class="product-info">
                                    <span class="product-name"><?php echo htmlspecialchars(
                                        $item["Descrizione"],
                                    ); ?></span>
                                </td>
                                <td><span class="qty-badge"><?php echo $item[
                                    "Quantita"
                                ]; ?></span></td>
                                <td class="price-cell"><?php echo number_format(
                                    $item["Prezzo"],
                                    2,
                                ); ?>€</td>
                                <td class="subtotal-cell"><?php echo number_format(
                                    $subtotale,
                                    2,
                                ); ?>€</td>
                                <td>
                                    <a href="carrello.php?rimuovi=<?php echo $item[
                                        "Prodotto_ID"
                                    ]; ?>" class="btn-remove">
                                        Rimuovi
                                    </a>
                                </td>
                            </tr>
                        <?php
                        endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="cart-footer">
                <div class="summary">
                    <div class="summary-row">
                        <span>Totale Parziale:</span>
                        <span><?php echo number_format($totale, 2); ?>€</span>
                    </div>
                    <div class="summary-row total">
                        <span>Totale Ordine:</span>
                        <span><?php echo number_format($totale, 2); ?>€</span>
                    </div>
                    <a href="checkout.php" class="btn-checkout">Procedi al Checkout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🛒</div>
                <h2>Il tuo carrello è vuoto</h2>
                <p>Non hai ancora aggiunto prodotti al tuo carrello.</p>
                <a href="catalogo.php" class="btn-checkout">Esplora il Catalogo</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>