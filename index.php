<?php
session_start();
// ---------- DATABASE CONNECTION ----------
$host = 'localhost';
$dbname = 'pantry';
$user = 'root';
$pass = 'mysql';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ---------------------------------------------------------------------------
$pantry_table = isset($_SESSION['pantry_table']) ? $_SESSION['pantry_table'] : 'foods';
$shop_table = isset($_SESSION['shop_table']) ? $_SESSION['shop_table'] : 'shop_list';

// EXPIRING SOON LIMIT (default 5 → expand to 10)
$exp_limit = 5;
if (isset($_GET['exp']) && $_GET['exp'] == 10) {
    $exp_limit = 10;
}

$expiringStmt = $pdo->prepare("SELECT name, expiration_date, quantity 
                               FROM `$pantry_table` 
                               ORDER BY expiration_date ASC 
                               LIMIT :lim");
$expiringStmt->bindValue(':lim', (int)$exp_limit, PDO::PARAM_INT);
$expiringStmt->execute();
$expiringFoods = $expiringStmt->fetchAll();

// ---------------------------------------------------------------------------
// GROCERY LIST LIMIT (default 5 → expand to 10)
$g_limit = 5;
if (isset($_GET['gro']) && $_GET['gro'] == 10) {
    $g_limit = 10;
}

$groceryStmt = $pdo->prepare("SELECT name, quantity, barcode 
                              FROM `$shop_table` 
                              ORDER BY id ASC 
                              LIMIT :lim");
$groceryStmt->bindValue(':lim', (int)$g_limit, PDO::PARAM_INT);
$groceryStmt->execute();
$groceryItems = $groceryStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Slick slider CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="index.css?v=2">
</head>
<body class="home-page">

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="nav-brand">
                    <img src="PantryPilotlogo2.png" style="height: 50px;">
                </a>
                <a href="index.php" class="active">Home</a>
                <a href="tracking.php">Pantry</a>
                <a href="grocery.php">Shopping List</a>
                <a href="about.php">About & Help</a>
            </div>
            <div class="nav-right">
                <a href="AccountInfo.php">Account Info</a>
                <a href="login.php" class="btn-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Slider -->
    <header class="hero-section">
        <div class="hero-slide-wrapper">
            <div class="hero-slide">
                <div><img src="pasta.jpg" alt="Jars of Pasta"></div>
                <div><img src="frozen-food.avif" alt= "Freezers with Food"></div>
                <div><img src="OIP.webp" alt="Fruit Stacked"></div>
                <div><img src="00-FOOD-PANTRIES-CLOSING-SAVEUR.webp" alt="Various Pantry Foods"></div>
                <div><img src="produce-vegetables.jpg" alt="Fresh Produce"></div>
                <div><img src="pantry-stuff.webp" alt="Jars and Juices on Shelves"></div>
            </div>
        </div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo" class="hero-logo">
            <h1>Welcome to Pantry Pilot</h1>
            <p>Your intelligent companion for seamless grocery and pantry management.</p>
            <div class="hero-buttons">
                <a href="tracking.php" class="btn-primary"><i class="fa-solid fa-box-open"></i> View Pantry</a>
                <a href="grocery.php" class="btn-secondary"><i class="fa-solid fa-cart-shopping"></i> Shopping List</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <div class="dashboard-grid">
            <!-- EXPIRING SOON SECTION -->
            <section class="dashboard-card" id="expiring-soon">
                <div class="card-header">
                    <h2><i class="fa-solid fa-triangle-exclamation" style="color:#ffb300;"></i> Expiring Soon</h2>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Expiration Date</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($expiringFoods) > 0): ?>
                                <?php foreach ($expiringFoods as $food): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($food['name']) ?></strong></td>
                                        <td><span class="badge badge-warning"><?= htmlspecialchars($food['expiration_date']) ?></span></td>
                                        <td><?= htmlspecialchars($food['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted"><br>No items expiring soon!<br><br></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <?php if ($exp_limit == 5): ?>
                        <a href="?exp=10&gro=<?= $g_limit ?>" class="btn-link">See More <i class="fa-solid fa-arrow-down"></i></a>
                    <?php else: ?>
                        <a href="?exp=5&gro=<?= $g_limit ?>" class="btn-link">See Less <i class="fa-solid fa-arrow-up"></i></a>
                    <?php endif; ?>
                </div>
            </section>

            <!-- CURRENT GROCERY LIST SECTION -->
            <section class="dashboard-card" id="current-grocery">
                <div class="card-header">
                    <h2><i class="fa-solid fa-cart-shopping" style="color:var(--primary-color);"></i> Shopping List</h2>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Quantity</th>
                                <th>Barcode</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($groceryItems) > 0): ?>
                                <?php foreach ($groceryItems as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                                        <td><span class="barcode-text"><?= htmlspecialchars($item['barcode'] ? $item['barcode'] : '—') ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="3" class="text-center text-muted"><br>Your shopping list is empty.<br><br></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <?php if ($g_limit == 5): ?>
                        <a href="?gro=10&exp=<?= $exp_limit ?>" class="btn-link">See More <i class="fa-solid fa-arrow-down"></i></a>
                    <?php else: ?>
                        <a href="?gro=5&exp=<?= $exp_limit ?>" class="btn-link">See Less <i class="fa-solid fa-arrow-up"></i></a>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.5.2/jquery-migrate.min.js" integrity="sha512-BzvgYEoHXuphX+g7B/laemJGYFdrq4fTKEo+B3PurSxstMZtwu28FHkPKXu6dSBCzbUWqz/rMv755nUwhjQypw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js" integrity="sha512-HGOnQO9+SP1V92SrtZfjqxxtLmVzqZpjFFekvzZVWoiASSQgSr4cw9Kqd2+l8Llp4Gm0G8GIFJ4ddwZilcdb8A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    
    <script src="app.js"></script>
</body>
</html>
