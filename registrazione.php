<?php
include "db.php";
$messaggio = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $user = trim($_POST["username"]);
    $pass = $_POST["password"];
    $confirm_pass = $_POST["confirm_password"];

    if ($pass !== $confirm_pass) {
        $messaggio = "Le password non coincidono!";
    } elseif (strlen($pass) < 6) {
        $messaggio = "La password deve essere di almeno 6 caratteri.";
    } else {
        $check = $pdo->prepare("SELECT * FROM Utente WHERE Username = ?");
        $check->execute([$user]);

        if ($check->rowCount() > 0) {
            $messaggio = "Username già esistente!";
        } else {
            // Hash della password prima di salvarla
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                "INSERT INTO Utente (Username, Password, Ruolo) VALUES (?, ?, 'cliente')",
            );
            if ($stmt->execute([$user, $hash])) {
                header("Location: login.php?registrato=1");
                exit();
            } else {
                $messaggio = "Errore durante la registrazione.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrazione - PC Master</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/login.css">
</head>
<body>

    <div class="login-wrapper">
        <div class="login-card">
            <div class="login-header">
                <h1>Crea Account</h1>
                <p>Unisciti alla nostra community hardware</p>
            </div>
            
            <?php if ($messaggio !== ""): ?>
                <div class="error-msg"><?php echo $messaggio; ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Scegli un nome utente" required>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Scegli una password" required>
                </div>
                <div class="input-group">
                    <label>Conferma Password</label>
                    <input type="password" name="confirm_password" placeholder="Ripeti la password" required>
                </div>
                <button type="submit" class="btn-submit">Registrati</button>
            </form>

            <div class="login-footer">
                <p>Hai già un account? <a href="login.php">Accedi qui</a></p>
                <a href="index.php" class="back-home">← Torna alla Home</a>
            </div>
        </div>
    </div>

</body>
</html>