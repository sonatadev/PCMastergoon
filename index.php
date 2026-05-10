<?php include "db.php"; ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PC Master - Home</title>
    <link rel="icon" href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/index.css">
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

<header class="hero">
    <div class="hero-content">
        <h1>Benvenuti nella Bottega di PC Master</h1>
        <p style="color: white">Componenti hardware selezionati e assemblati con cura artigianale. Deh, pefforza.</p>
        <div class="hero-btns">
            <a href="catalogo.php" class="btn-primary">Sfoglia il Catalogo</a>
            <a href="#ultimi-arrivi" class="btn-secondary">Scopri le Novità</a>
        </div>
    </div>
</header>

<main class="container" id="ultimi-arrivi">
    <div class="section-header">
        <h2>Ultimi Arrivi</h2>
        <p>Le ultime tecnologie caricate nel nostro database.</p>
    </div>
    
    <div class="product-grid">
        <?php
        $stmt = $pdo->query("SELECT p.*, i.Nome_file 
                             FROM Prodotto p 
                             LEFT JOIN Immagini i ON p.Prodotto_ID = i.Prodotto_ID AND i.Principale = 1
                             ORDER BY p.Prodotto_ID DESC 
                             LIMIT 3");

        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <article class="product-card">
                <div class="card-image">
                    <?php if ($p["Nome_file"]): ?>
                        <img src="<?php echo $p[
                            "Nome_file"
                        ]; ?>" alt="Prodotto">
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <span class="badge"><?php echo htmlspecialchars(
                        $p["Categoria"],
                    ); ?></span>
                    <h3><?php echo htmlspecialchars($p["Descrizione"]); ?></h3>
                    <p class="price"><?php echo number_format(
                        $p["Prezzo"],
                        2,
                        ",",
                        ".",
                    ); ?>€</p>
                    <a href="prodotto.php?id=<?php echo $p[
                        "Prodotto_ID"
                    ]; ?>" class="btn-card">Vedi Dettagli</a>
                </div>
            </article>
        <?php endwhile;
        ?>
    </div>
</main>

<footer class="main-footer">
    <div class="footer-content">
        <p>&copy; 2026 <strong>PC Master Goon</strong> - Progetto PCTO Gruppo 2</p>
        <p>Hardware serio per gamer seri.</p>
        <img src="img/synergsminimo.jpg" alt="Synergy">
    </div>
</footer>

</body>
</html>