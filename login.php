<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = $_POST["username"];
    $pass = $_POST["password"];

    // Recupera utente per username (senza confrontare la password nel SQL)
    $stmt = $pdo->prepare("SELECT * FROM Utente WHERE Username = ?");
    $stmt->execute([$user]);
    $u = $stmt->fetch();

    // Verifica password: supporta sia hash che plain text (per retrocompatibilità)
    $passwordOk = false;
    if ($u) {
        if (password_verify($pass, $u["Password"])) {
            // Password hashata correttamente
            $passwordOk = true;
        } elseif ($u["Password"] === $pass) {
            // Password ancora in plain text — la aggiorniamo subito all'hash
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $pdo->prepare(
                "UPDATE Utente SET Password = ? WHERE Utente_ID = ?",
            )->execute([$hash, $u["Utente_ID"]]);
            $passwordOk = true;
        }
    }

    if ($passwordOk) {
        $_SESSION["utente_id"] = $u["Utente_ID"];
        $_SESSION["ruolo"] = $u["Ruolo"];
        header("Location: index.php");
        exit();
    } else {
        $errore = "Credenziali errate!";
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/login.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <a href="index.php" class="brand logo-only"></a>
                <h1>Bentornato</h1>
                <p>Accedi per gestire i tuoi ordini</p>
            </div>
            
            <?php if (isset($_GET["registrato"])): ?>
                <div class="success-msg">Registrazione completata! Accedi ora.</div>
            <?php endif; ?>

            <?php if (isset($errore)): ?>
                <div class="error-msg"><?php echo $errore; ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Inserisci il tuo username" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit">Accedi</button>
            </form>

            <div class="login-footer">
                <p>Nuovo cliente? <a href="registrazione.php">Crea un account</a></p>
                <a href="index.php" class="back-home">← Torna alla Home</a>
            </div>
        </div>
    </div>

</body>
</html>