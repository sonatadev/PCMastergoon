<?php
include "db.php";

if (!isset($_SESSION["utente_id"])) {
    header("Location: login.php");
    exit();
}

$u_id = $_SESSION["utente_id"];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>I Miei Ordini - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/navbar.css">
    <link rel="stylesheet" href="stylesheet/dettaglio_ordine.css"> 
    <style>
        .orders-container { max-width: 1000px; margin: 50px auto; padding: 20px; }
        .order-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .order-table th, .order-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .order-table th { background-color: #f8f9fa; font-weight: 700; color: #333; }
        .btn-view { background: #007bff; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 0.9rem; }
        .btn-view:hover { background: #0056b3; }
        .no-orders { text-align: center; padding: 40px; color: #666; }
    </style>
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

    <div class="orders-container">
        <h1>I Tuoi Ordini</h1>
        <p>Cronologia degli acquisti effettuati.</p>

        <?php
        $sql = "SELECT o.Ordine_ID, o.Data, o.Metodo_pagamento, 
                       SUM(od.QA_prodotto * p.Prezzo) AS Totale
                FROM Ordine o
                JOIN Ordine_Dettaglio od ON o.Ordine_ID = od.Ordine_ID
                JOIN Prodotto p ON od.Prodotto_ID = p.Prodotto_ID
                WHERE o.Utente_ID = ?
                GROUP BY o.Ordine_ID
                ORDER BY o.Data DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$u_id]);
        $ordini = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($ordini) > 0): ?>
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Data Acquisto</th>
                        <th>Metodo di Pagamento</th>
                        <th>Totale</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordini as $o): ?>
                        <tr>
                            <td><?php echo date(
                                "d/m/Y H:i",
                                strtotime($o["Data"]),
                            ); ?></td>
                            <td><?php echo htmlspecialchars(
                                $o["Metodo_pagamento"],
                            ); ?></td>
                            <td><strong><?php echo number_format(
                                $o["Totale"],
                                2,
                                ",",
                                ".",
                            ); ?>€</strong></td>
                            <td>
                                <a href="dettaglio_ordine.php?id=<?php echo $o[
                                    "Ordine_ID"
                                ]; ?>" class="btn-view">Vedi Dettagli</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-orders">
                <p>Non hai ancora effettuato ordini.</p>
                <a href="catalogo.php" class="btn-view">Inizia lo shopping</a>
            </div>
        <?php endif;
        ?>
    </div>
</body>
</html>