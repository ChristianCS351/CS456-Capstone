<?php
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

/* ------------------ DELETE ITEM ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = :id");
    $stmt->execute([':id' => $delete_id]);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ------------------ ADD ITEM TO SHOPPING LIST ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];
    $stmt = $pdo->prepare("SELECT name, quantity, barcode FROM foods WHERE id = :id");
    $stmt->execute([':id' => $shop_id]);
    $item = $stmt->fetch();

    if ($item) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM shop_list WHERE name = :name");
        $check->execute([':name' => $item['name']]);
        $exists = $check->fetchColumn();

        if ($exists == 0) {
            $stmt2 = $pdo->prepare("INSERT INTO shop_list (name, quantity, barcode) VALUES (:name, :quantity, :barcode)");
            $stmt2->execute([
                ':name'     => $item['name'],
                ':quantity' => $item['quantity'],
                ':barcode'  => $item['barcode']
            ]);
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ------------------ INSERT NEW ITEM ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $expiration_date = trim($_POST['expiration_date']);
    $location = trim($_POST['location']);
    $dairy = trim($_POST['dairy']);
    $quantity = (int)$_POST['quantity'];
    $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
    if ($barcode === '') { $barcode = null; }

    if (empty($name) || empty($expiration_date) || empty($location) || $quantity <= 0) {
        echo "<script>alert('Please fill in all required fields correctly before submitting.');</script>";
    } else {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO foods (name, expiration_date, quantity, location, dairy, open_date, open_expiration_date, barcode) VALUES (:name, :expiration_date, :quantity, :location, :dairy, :open_date, :open_expiration_date, :barcode)");
        $stmt->execute([
            ':name' => $name,
            ':expiration_date' => $expiration_date,
            ':quantity' => $quantity,
            ':location' => $location,
            ':dairy' => $dairy,
            ':open_date' => $today,
            ':open_expiration_date' => date('Y-m-d', strtotime('+1 year')),
            ':barcode' => $barcode
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ------------------ FETCH PANTRY ITEMS ------------------ */
$stmt = $pdo->query("SELECT id, name, quantity, expiration_date, location FROM foods");
$pantry_items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantry - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="tracking.css">
</head>
<body class="tracking-page">

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="nav-brand">
                    <i class="fa-solid fa-plane-departure"></i> Pantry Pilot
                </a>
                <a href="index.php">Home</a>
                <a href="tracking.php" class="active">Pantry</a>
                <a href="grocery.php">Shopping List</a>
                <a href="about.php">About & Help</a>
            </div>
            <div class="nav-right">
                <a href="AccountInfo.php">Account Info</a>
                <a href="login.php" class="btn-login">Login</a>
            </div>
        </div>
    </nav>

    <!-- Mini Hero Section -->
    <header class="mini-hero">
        <div class="mini-hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><i class="fa-solid fa-box-open"></i> My Pantry</h1>
            <p>Manage your ingredients, track expiration dates, and restock seamlessly.</p>
        </div>
    </header>

    <main class="main-content">
        <div class="split-layout">
            
            <!-- Left: Pantry Table -->
            <section class="pantry-section card-modern">
                <div class="card-header">
                    <h2><i class="fa-solid fa-cubes"></i> Current Inventory</h2>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>QTY</th>
                                <th>Expiration</th>
                                <th>Location</th>
                                <th class="text-center" colspan="2">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pantry_items)): ?>
                                <?php foreach ($pantry_items as $item): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                        <td><span class="badge badge-qty"><?= htmlspecialchars($item['quantity']) ?></span></td>
                                        <td><?= htmlspecialchars($item['expiration_date']) ?></td>
                                        <td><span class="badge badge-location"><?= htmlspecialchars($item['location']) ?></span></td>
                                        <td class="action-cell">
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-action btn-danger" title="Remove Item"><i class="fa-solid fa-trash-can"></i> Remove</button>
                                            </form>
                                        </td>
                                        <td class="action-cell">
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="shop_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-action btn-warning" title="Add to Shopping List"><i class="fa-solid fa-cart-plus"></i> Shop</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="text-center text-muted"><br>No items found in pantry.<br>Use the form on the right to add some!<br><br></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Right: Add Item & Scanner -->
            <aside class="sidebar-section">
                <!-- Add Item Form -->
                <div class="form-section card-modern">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-plus-circle"></i> Add an Item</h2>
                    </div>
                    <form method="POST" action="" id="add_item_form" class="modern-form">
                        <div class="input-group">
                            <label><i class="fa-solid fa-tag"></i> Name</label>
                            <input type="text" name="name" required placeholder="e.g. Canned Beans">
                        </div>
                        
                        <div class="input-group">
                            <label><i class="fa-solid fa-calendar-alt"></i> Expiration Date</label>
                            <input type="date" name="expiration_date" required>
                        </div>
                        
                        <div class="input-group">
                            <label><i class="fa-solid fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" required placeholder="e.g. Bottom Shelf">
                        </div>
                        
                        <div class="grid-2-col">
                            <div class="input-group">
                                <label><i class="fa-solid fa-shapes"></i> Category</label>
                                <input type="text" name="dairy" placeholder="Optional">
                            </div>
                            <div class="input-group">
                                <label><i class="fa-solid fa-layer-group"></i> QTY</label>
                                <input type="number" name="quantity" min="1" required value="1">
                            </div>
                        </div>

                        <input type="hidden" name="barcode" id="barcode">
                        
                        <button type="submit" name="add_item" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Save Item
                        </button>
                    </form>
                </div>

                <!-- Barcode Scanner -->
                <div class="scan-section card-modern mt-4">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-barcode"></i> Quick Scan</h2>
                    </div>
                    <div class="scan-body text-center">
                        <p class="text-muted mb-3">Scan product barcodes to instantly import details.</p>
                        <button type="button" class="btn-scan" id="scan_btn"><i class="fa-solid fa-camera"></i> Scan Barcode</button>
                        <div id="reader" style="width:100%; max-width:100%; margin:15px auto; display:none; border-radius: 8px; overflow: hidden;"></div>
                        <p id="scan_result" class="scan-result-text"></p>
                    </div>
                </div>
            </aside>
        </div>
    </main>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-logo">
                <i class="fa-solid fa-plane-departure"></i> Pantry Pilot
            </div>
            <p>&copy; 2026 Pantry Pilot. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="barcode_scanner.js"></script>
</body>
</html>
