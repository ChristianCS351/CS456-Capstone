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


if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_barcode'])) {
    $barcode = $_GET['check_barcode'];
    
    // First, check if it's currently in the pantry
    $stmt = $pdo->prepare("SELECT id, name FROM foods WHERE barcode = :barcode OR id = :id_barcode LIMIT 1");
    $stmt->execute([
        ':barcode' => $barcode,
        ':id_barcode' => $barcode
    ]);
    $item = $stmt->fetch();
    
    header('Content-Type: application/json');
    if ($item) {
        // Exists in current pantry
        echo json_encode(['exists' => true, 'name' => $item['name']]);
    } else {
        // Doesn't exist in pantry, check history table for auto-fill data
        $hist_stmt = $pdo->prepare("SELECT name, dairy FROM food_history WHERE barcode = :barcode LIMIT 1");
        $hist_stmt->execute([':barcode' => $barcode]);
        $history = $hist_stmt->fetch();
        
        if ($history) {
            echo json_encode(['exists' => false, 'history_name' => $history['name'], 'history_dairy' => $history['dairy']]);
        } else {
            echo json_encode(['exists' => false]);
        }
    }
    exit;
}

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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_update_item'])) {
    $barcode = $_POST['update_barcode'];
    $expiration_date = trim($_POST['update_expiration_date']);
    
    $stmt = $pdo->prepare("UPDATE foods SET quantity = quantity + 1, expiration_date = :expiration_date WHERE barcode = :barcode OR id = :id_barcode");
    $stmt->execute([
        ':expiration_date' => $expiration_date,
        ':barcode' => $barcode,
        ':id_barcode' => $barcode
    ]);
    
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
        
        if ($barcode) {
            // Because user wants Barcode to be Primary Key, we must also save this item to the food_history table
            $hist_stmt = $pdo->prepare("INSERT IGNORE INTO food_history (barcode, name, dairy) VALUES (:barcode, :name, :dairy)");
            $hist_stmt->execute([
                ':barcode' => $barcode,
                ':name' => $name,
                ':dairy' => $dairy
            ]);
            
            $stmt = $pdo->prepare("INSERT INTO foods (id, name, expiration_date, quantity, location, dairy, open_date, open_expiration_date, barcode) 
                                   VALUES (:id, :name, :expiration_date, :quantity, :location, :dairy, :open_date, :open_expiration_date, :barcode)
                                   ON DUPLICATE KEY UPDATE quantity = quantity + :update_quantity");
            $stmt->execute([
                ':id' => $barcode,
                ':name' => $name,
                ':expiration_date' => $expiration_date,
                ':quantity' => $quantity,
                ':location' => $location,
                ':dairy' => $dairy,
                ':open_date' => $today,
                ':open_expiration_date' => date('Y-m-d', strtotime('+1 year')),
                ':barcode' => $barcode,
                ':update_quantity' => $quantity
            ]);
        } else {
            // Let ID Auto Increment
            $stmt = $pdo->prepare("INSERT INTO foods (name, expiration_date, quantity, location, dairy, open_date, open_expiration_date, barcode) 
                                   VALUES (:name, :expiration_date, :quantity, :location, :dairy, :open_date, :open_expiration_date, :barcode)");
            $stmt->execute([
                ':name' => $name,
                ':expiration_date' => $expiration_date,
                ':quantity' => $quantity,
                ':location' => $location,
                ':dairy' => $dairy,
                ':open_date' => $today,
                ':open_expiration_date' => date('Y-m-d', strtotime('+1 year')),
                ':barcode' => null
            ]);
        }

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
                        <div class="input-group">
                            <label><i class="fa-solid fa-barcode"></i> Barcode (Optional)</label>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" name="barcode" id="barcode" placeholder="Scan or type barcode" class="form-control" style="flex: 1;">
                                <button type="button" class="btn-scan" id="scan_btn" style="white-space: nowrap;"><i class="fa-solid fa-camera"></i> Scan</button>
                            </div>
                            <div id="reader" style="width:100%; max-width:100%; margin:15px auto; display:none; border-radius: 8px; overflow: hidden;"></div>
                            <p id="scan_result" class="scan-result-text"></p>
                        </div>
                        
                        <button type="submit" name="add_item" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Save Item
                        </button>
                    </form>
                </div>

                <!-- Quick Scan Section -->
                <div class="scan-section card-modern mt-4">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-barcode"></i> Quick Scan</h2>
                    </div>
                    <div class="scan-body text-center">
                        <p class="text-muted mb-3">Scan product barcodes to instantly import details.</p>
                        <button type="button" class="btn-scan" id="quick_scan_btn"><i class="fa-solid fa-camera"></i> Quick Scan</button>
                        <div id="quick_reader" style="width:100%; max-width:100%; margin:15px auto; display:none; border-radius: 8px; overflow: hidden;"></div>
                        <p id="quick_scan_result" class="scan-result-text"></p>
                    </div>
                </div>
            </aside>
        </div>
    </main>
    <!-- Quick Scan Update Modal -->
    <div id="quickScanModal" class="modal-overlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
        <div class="modal-content card-modern" style="background: white; padding: 25px; border-radius: 12px; max-width: 400px; width: 90%; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <h2 style="margin-top: 0; color: #333;"><i class="fa-solid fa-sync" style="color: var(--primary);"></i> Item Found!</h2>
            <p id="modal_item_name" style="font-weight: 600; font-size: 1.2rem; color: #555; margin-bottom: 20px;">Item Name</p>
            <p style="color: #666; margin-bottom: 20px; text-align: left;">This item already exists in your pantry. Please provide its fresh expiration date to automatically add +1 to the quantity.</p>
            <form method="POST" action="" id="quick_update_form" class="modern-form">
                <input type="hidden" name="update_barcode" id="modal_barcode">
                <div class="input-group" style="text-align: left; margin-bottom: 20px;">
                    <label><i class="fa-solid fa-calendar-alt"></i> New Expiration Date</label>
                    <input type="date" name="update_expiration_date" required class="form-control" style="width: 100%;">
                </div>
                <div style="display: flex; gap: 10px; justify-content: center;">
                    <button type="submit" name="quick_update_item" class="btn-submit" style="flex: 1;"><i class="fa-solid fa-plus"></i> Add +1</button>
                    <button type="button" onclick="document.getElementById('quickScanModal').style.display='none';" class="btn-scan" style="background: #ccc; flex: 1; border-radius: 30px;"><i class="fa-solid fa-times"></i> Cancel</button>
                </div>
            </form>
        </div>
    </div>

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
    <script src="barcode_scanner.js?v=1.1"></script>
</body>
</html>
