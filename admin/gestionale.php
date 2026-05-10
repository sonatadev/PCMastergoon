<?php
include __DIR__ . "/../db.php";

// Controllo sicurezza — rispondi con 401 JSON per chiamate AJAX
if (!isset($_SESSION["ruolo"]) || $_SESSION["ruolo"] !== "admin") {
    if (
        $_SERVER["REQUEST_METHOD"] === "POST" &&
        (isset($_POST["elimina_immagine"]) || isset($_POST["salva_principale"]))
    ) {
        http_response_code(401);
        echo json_encode(["error" => "session_expired"]);
        exit();
    }
    header("Location: ../index.php");
    exit();
}

// ELIMINAZIONE SINGOLA IMMAGINE (Chiamata da Fetch JS)
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["elimina_immagine"])
) {
    $nome_file = $_POST["nome_file"];
    $file_path = __DIR__ . "/../" . $nome_file;

    if (file_exists($file_path)) {
        unlink($file_path);
    }

    $pdo->prepare("DELETE FROM Immagini WHERE Nome_file = ?")->execute([
        $nome_file,
    ]);

    http_response_code(200);
    echo json_encode(["success" => true]);
    exit();
}

// FIX 3: IMPOSTA IMMAGINE PRINCIPALE (chiamata automatica dopo eliminazione principale)
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["salva_principale"])
) {
    $prodotto_id = intval($_POST["prodotto_id"]);
    $nome_file = $_POST["nome_file"];

    $pdo->prepare(
        "UPDATE Immagini SET Principale = 0 WHERE Prodotto_ID = ?",
    )->execute([$prodotto_id]);
    $pdo->prepare(
        "UPDATE Immagini SET Principale = 1 WHERE Nome_file = ? AND Prodotto_ID = ?",
    )->execute([$nome_file, $prodotto_id]);

    http_response_code(200);
    echo json_encode(["success" => true]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["salva_prodotto"])) {
    $id = $_POST["prodotto_id"];
    $desc = $_POST["descrizione"];
    $prezzo = $_POST["prezzo"];
    $cat = $_POST["categoria"];
    $qty = $_POST["quantita"];

    if (empty($id)) {
        $sql =
            "INSERT INTO Prodotto (Descrizione, Prezzo, Categoria, Quantita_magazzino) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$desc, $prezzo, $cat, $qty]);
        $id = $pdo->lastInsertId();
    } else {
        $sql =
            "UPDATE Prodotto SET Descrizione=?, Prezzo=?, Categoria=?, Quantita_magazzino=? WHERE Prodotto_ID=?";
        $pdo->prepare($sql)->execute([$desc, $prezzo, $cat, $qty, $id]);

        if (isset($_POST["nuova_immagine_principale"])) {
            $nuova_main = $_POST["nuova_immagine_principale"];
            $pdo->prepare(
                "UPDATE Immagini SET Principale = 0 WHERE Prodotto_ID = ?",
            )->execute([$id]);
            $pdo->prepare(
                "UPDATE Immagini SET Principale = 1 WHERE Nome_file = ? AND Prodotto_ID = ?",
            )->execute([$nuova_main, $id]);
        }
    }

    if (!empty($_FILES["immagini"]["name"][0])) {
        $target_dir = __DIR__ . "/../img/";

        // Cicliamo l'array dei file
        foreach ($_FILES["immagini"]["name"] as $key => $val) {
            if ($_FILES["immagini"]["error"][$key] == 0) {
                $original_name = basename($_FILES["immagini"]["name"][$key]);
                $file_name =
                    time() .
                    "_" .
                    $key .
                    "_" .
                    preg_replace("/[^a-zA-Z0-9.]/", "_", $original_name);
                $file_path = $target_dir . $file_name;

                if (
                    move_uploaded_file(
                        $_FILES["immagini"]["tmp_name"][$key],
                        $file_path,
                    )
                ) {
                    $db_value = "img/" . $file_name;

                    // Controlla se esiste già una principale per questo prodotto
                    $checkMain = $pdo->prepare(
                        "SELECT COUNT(*) FROM Immagini WHERE Prodotto_ID = ? AND Principale = 1",
                    );
                    $checkMain->execute([$id]);
                    $isFirst = $checkMain->fetchColumn() == 0 ? 1 : 0;

                    $sql_img =
                        "INSERT INTO Immagini (Nome_file, Prodotto_ID, Principale) VALUES (?, ?, ?)";
                    $pdo->prepare($sql_img)->execute([
                        $db_value,
                        $id,
                        $isFirst,
                    ]);
                }
            }
        }
    }

    header("Location: gestionale.php?success=1");
    exit();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Prodotti - PC Master</title>
    <link rel="stylesheet" href="../stylesheet/admin.css">
    <link rel="stylesheet" href="../stylesheet/admin_sidebar.css">
    <link rel="stylesheet" href="../stylesheet/gestionale.css">
</head>
<body>
    <div class="admin-sidebar">
        <h2>PC Master Admin</h2>
        <a href="../index.php">Sito Pubblico</a>
        <hr>
        <a href="gestionale.php"><strong>Gestione Prodotti</strong></a>
        <a href="area_admin.php">Gestione Ordini</a>
        <hr>
        <a href="../logout.php" style="color: #e74c3c;">Logout</a>
    </div>

    <div class="admin-content">
        <h1>Gestione Prodotti</h1>
        <button class="btn-open-modal" onclick="openModal()">+ Aggiungi Nuovo Prodotto</button>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Descrizione</th>
                    <th>Prezzo</th>
                    <th>Stock</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
    <?php
    $stmtProd = $pdo->query("SELECT * FROM Prodotto ORDER BY Prodotto_ID DESC");
    while ($p = $stmtProd->fetch(PDO::FETCH_ASSOC)):

        $stmtImg = $pdo->prepare(
            "SELECT * FROM Immagini WHERE Prodotto_ID = ?",
        );
        $stmtImg->execute([$p["Prodotto_ID"]]);
        $p["immagini"] = $stmtImg->fetchAll(PDO::FETCH_ASSOC);
        $jsonData = htmlspecialchars(json_encode($p), ENT_QUOTES, "UTF-8");
        ?>
    <tr>
        <td>#<?= $p["Prodotto_ID"] ?></td>
        <td><strong><?= htmlspecialchars($p["Descrizione"]) ?></strong></td>
        <td>€<?= number_format($p["Prezzo"], 2) ?></td>
        <td><?= $p["Quantita_magazzino"] ?></td>
        <td>
            <button class="btn-detail" onclick='openModal(<?= $jsonData ?>)'>Modifica</button>
        </td>
    </tr>
    <?php
    endwhile;
    ?>
            </tbody>
        </table>
    </div>

    <div id="productModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2 id="modalTitle">Dettagli Prodotto</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="prodotto_id" id="f_id">
            
            <label>Descrizione / Nome</label>
            <textarea name="descrizione" id="f_desc" required></textarea>
            
            <label>Categoria</label>
            <input type="text" name="categoria" id="f_cat">
            
            <div style="display:flex; gap:10px;">
                <div style="flex:1">
                    <label>Prezzo (€)</label>
                    <input type="number" step="0.01" name="prezzo" id="f_prezzo" required>
                </div>
                <div style="flex:1">
                    <label>Stock</label>
                    <input type="number" name="quantita" id="f_qty">
                </div>
            </div>

            <div id="existingImagesSection" style="display:none; margin-top: 20px;">
                <label>Immagini Caricate</label>
                <table class="img-manage-table" style="width:100%; border-collapse: collapse;">
                    <tbody id="imgListTable"></tbody>
                </table>
            </div>

            <label id="uploadLabel" style="margin-top:15px; display:block;">Aggiungi Immagini</label>
            <input type="file" name="immagini[]" accept="image/*" multiple>

            <button type="submit" name="salva_prodotto" class="btn-save">Salva Prodotto</button>

            <div id="deleteSection" class="danger-zone" style="display: none;">
                <span class="danger-label">Zona di Pericolo</span>
                <button type="button" class="btn-delete-modal" onclick="showConfirmPopup()">Elimina Prodotto</button>
            </div>
        </form>
    </div>
</div>

    <div id="confirmPopup" class="confirm-overlay">
        <div class="confirm-card">
            <h3>Sei sicuro?</h3>
            <p>L'azione è irreversibile. Il prodotto e le relative immagini verranno rimosse.</p>
            <div class="confirm-buttons">
                <button type="button" class="btn-cancel" onclick="closeConfirmPopup()">Annulla</button>
                <a id="finalDeleteLink" href="#" class="btn-confirm-final">Elimina</a>
            </div>
        </div>
    </div>

    <div id="confirmImagePopup" class="confirm-overlay">
    <div class="confirm-card">
        <h3>Eliminare Immagine?</h3>
        <p>L'immagine verrà rimossa definitivamente dal server e dal database.</p>
        <div class="confirm-buttons">
            <button type="button" class="btn-cancel" onclick="closeImageConfirm()">Annulla</button>
            <button type="button" id="btnConfirmImgDelete" class="btn-confirm-final">Elimina</button>
        </div>
    </div>
</div>

    <script src="../js/script_admin.js"></script>
</body>
</html>

