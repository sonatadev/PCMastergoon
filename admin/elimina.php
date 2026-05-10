<?php
include __DIR__ . "/../db.php";

if (!isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "admin") {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];

    try {
        $pdo->beginTransaction();

        // Elimina prima le immagini fisiche dal server
        $stmtImgs = $pdo->prepare(
            "SELECT Nome_file FROM Immagini WHERE Prodotto_ID = ?",
        );
        $stmtImgs->execute([$id]);
        foreach ($stmtImgs->fetchAll(PDO::FETCH_COLUMN) as $file) {
            $path = __DIR__ . "/../../" . $file;
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $pdo->prepare("DELETE FROM Immagini WHERE Prodotto_ID = ?")->execute([
            $id,
        ]);
        $pdo->prepare(
            "DELETE FROM Ordine_Dettaglio WHERE Prodotto_ID = ?",
        )->execute([$id]);
        $pdo->prepare("DELETE FROM Prodotto WHERE Prodotto_ID = ?")->execute([
            $id,
        ]);

        $pdo->commit();

        header("Location: gestionale.php?deleted=1");
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack(); ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="UTF-8">
            <title>Errore - PC Master</title>
            <link rel="stylesheet" href="../stylesheet/elimina.css">
        </head>
        <body>
            <div class="delete-container">
                <div class="error-icon">⚠️</div>
                <h1>Si è verificato un errore</h1>
                <div class="error-box">
                    <?= htmlspecialchars($e->getMessage()) ?>
                </div>
                <p>Non è stato possibile completare l'operazione. Torna al gestionale e riprova.</p>
                <a href="gestionale.php" class="btn-back">Torna al Gestionale</a>
            </div>
        </body>
        </html>
        <?php
    }
}
?>
