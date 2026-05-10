<?php include "../db.php";
// Controllo sicurezza: solo admin
if (!isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "admin") {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Area Admin - Gestione Ordini</title>
    <link rel="icon"  href="img/logo mini no bg.png">
    <link rel="stylesheet" href="../stylesheet/admin.css">
    <link rel="stylesheet" href="../stylesheet/admin_sidebar.css">
</head>
<body>
    <div class="admin-sidebar">
    <h2>PC Master Admin</h2>
    <a href="../index.php">Sito Pubblico</a>
    <hr>
    <a href="gestionale.php">Gestione Prodotti</a>
    <a href="area_admin.php"><strong>Gestione Ordini</strong></a>
    <hr>
    <a href="../logout.php" style="color: #e74c3c;">Logout</a>
</div>

    <div class="admin-content">
        <h1>Elenco Ordini Ricevuti</h1>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID Ordine</th>
                    <th>Cliente</th> 
                    <th>Indirizzo</th>
                    <th>Metodo</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $sql = "SELECT o.Ordine_ID, o.Indirizzo, o.Metodo_pagamento, u.Username 
                    FROM Ordine o 
                    JOIN Utente u ON o.Utente_ID = u.Utente_ID
                    ORDER BY o.Ordine_ID DESC"; // Ordini più recenti in alto

            $stmt = $pdo->query($sql);

            while ($o = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr style="cursor: pointer;" onclick="window.location='../dettaglio_ordine.php?id=<?php echo $o[
                    "Ordine_ID"
                ]; ?>'">
                    <td>#<?php echo $o["Ordine_ID"]; ?></td>
                    
                    <td><strong><?php echo htmlspecialchars(
                        $o["Username"],
                    ); ?></strong></td> 
                    
                    <td><?php echo htmlspecialchars($o["Indirizzo"]); ?></td>
                    <td><?php echo htmlspecialchars(
                        $o["Metodo_pagamento"],
                    ); ?></td>
                    <td><a href="../dettaglio_ordine.php?id=<?php echo $o[
                        "Ordine_ID"
                    ]; ?>" class="btn-detail">Vedi Dettagli</a></td>
                </tr>
            <?php endwhile;
            ?>
            </tbody>
        </table>
    </div>
</body>
</html>