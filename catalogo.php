<?php include "db.php"; ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogo - PC Master</title>
    <link rel="icon"  href="img/logo mini no bg.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="stylesheet/catalogo.css">
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
        <h1>Catalogo Componenti</h1>
        <p>Potenzia il tuo setup con i migliori componenti sul mercato.</p>
    </header>

    <?php
    // Query ottimizzata: prende il prodotto e solo l'immagine marchiata come principale (Principale = 1)
    $stmt = $pdo->query("SELECT p.*, i.Nome_file 
                            FROM Prodotto p 
                            LEFT JOIN Immagini i ON p.Prodotto_ID = i.Prodotto_ID AND i.Principale = 1");

    $prodotti = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Estraiamo le categorie per i filtri laterali
    $categorie = array_unique(array_column($prodotti, "Categoria"));
    ?>

    <div class="main-container">
        <aside class="filter-sidebar">
            <h3>Filtra per Categoria</h3>
            <div class="checkbox-group">
                <?php foreach ($categorie as $cat):
                    $slug = str_replace(" ", "", $cat); ?>
                    <label class="checkbox-label">
                        <input type="checkbox" class="category-filter" value="<?php echo $slug; ?>"> 
                        <span class="custom-check"></span>
                        <?php echo $cat; ?>
                    </label>
                <?php
                endforeach; ?>
            </div>
        </aside>

        <section class="grid" id="product-grid">
            <?php foreach ($prodotti as $p):
                $cat_slug = str_replace(" ", "", $p["Categoria"]); ?>
                <div class="card product-item <?php echo $cat_slug; ?>">
                    <div class="card-img">
                        <img src="<?php echo $p["Nome_file"]
                            ? $p["Nome_file"]
                            : "img/placeholder.png"; ?>" alt="Prodotto">
                    </div>
                    <div class="card-content">
                        <span class="category-tag"><?php echo $p[
                            "Categoria"
                        ]; ?></span>
                        <h3><?php echo htmlspecialchars(
                            $p["Descrizione"],
                        ); ?></h3>
                        <p class="price"><?php echo number_format(
                            $p["Prezzo"],
                            2,
                        ); ?>€</p>
                        <a href="prodotto.php?id=<?php echo $p[
                            "Prodotto_ID"
                        ]; ?>" class="btn-details">Vedi Dettagli</a>
                    </div>
                </div>
            <?php
            endforeach; ?>
        </section>
    </div>

    <script>
        const checkboxes = document.querySelectorAll('.category-filter');
        const items = document.querySelectorAll('.product-item');

        checkboxes.forEach(box => {
            box.addEventListener('change', () => {
                const activeFilters = Array.from(checkboxes)
                                           .filter(i => i.checked)
                                           .map(i => i.value);

                items.forEach(item => {
                    if (activeFilters.length === 0) {
                        item.style.display = 'flex'; // Mostra tutto se nulla è selezionato
                    } else {
                        const isMatch = activeFilters.some(filter => item.classList.contains(filter));
                        item.style.display = isMatch ? 'flex' : 'none';
                    }
                });
            });
        });
    </script>
</body>
</html>