<?php
session_start();

/* ------------------ DB CONFIG ------------------ */
$host = 'localhost';
$db   = 'pantry';
$user = 'root';
$pass = 'mysql';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("DB Connection Failed: " . $e->getMessage());
}

/* ------------------ TABLES ------------------ */
$pantry_table = $_SESSION['pantry_table'] ?? 'foods';
$shop_table   = $_SESSION['shop_table'] ?? 'shop_list';

function safeTable($name) {
    return preg_match('/^[a-zA-Z0-9_]+$/', $name) ? $name : 'foods';
}
$pantry_table = safeTable($pantry_table);
$shop_table   = safeTable($shop_table);

function redirect() {
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Prevent access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ============================================================
   CHECK BARCODE
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['check_barcode'])) {

    $barcode = trim($_GET['check_barcode']);

    $stmt = $pdo->prepare("
        SELECT name 
        FROM `$pantry_table`
        WHERE barcode = ? OR id = ?
        LIMIT 1
    ");
    $stmt->execute([$barcode, $barcode]);
    $item = $stmt->fetch();

    header('Content-Type: application/json');

    if ($item) {
        echo json_encode(['exists' => true, 'name' => $item['name']]);
    } else {
        $hist = $pdo->prepare("
            SELECT name, category 
            FROM food_history 
            WHERE barcode = ? 
            LIMIT 1
        ");
        $hist->execute([$barcode]);
        $h = $hist->fetch();

        echo json_encode($h
            ? ['exists' => false, 'history_name' => $h['name'], 'history_category' => $h['category']]
            : ['exists' => false]
        );
    }
    exit;
}


$sort = isset($_GET['sort_table']) ? $_GET['sort_table'] : 'when_add';

switch ($sort) {
     case 'qty_desc':
        $orderBy = "quantity DESC";
        break;
     case 'qty_asc':
        $orderBy = "quantity ASC";
        break;
     case 'name_desc':
        $orderBy = "name DESC";
        break;
     case 'name_asc':
        $orderBy = "name ASC";
        break;
     case 'exp_desc':
        $orderBy = "expiration_date DESC";
        break;
     case 'exp_asc':
        $orderBy = "expiration_date ASC";
        break;
     case 'open_desc':
        $orderBy = "open_date DESC";
        break;
     case 'open_asc':
        $orderBy = "open_date ASC";
        break;
     case 'loc_desc':
        $orderBy = "location DESC";
        break;
     case 'loc_asc':
        $orderBy = "location ASC";
        break;
     case 'cat_desc':
        $orderBy = "category DESC";
        break;
     case 'cat_asc':
        $orderBy = "category ASC";
        break;
     default:
        $orderBy = "id DESC";
        break;
}
$stmt = $pdo->query("SELECT id, name, quantity FROM `$pantry_table` ORDER BY $orderBy");
$shop_items = $stmt->fetchAll();


/* ============================================================
   DELETE ITEM
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $pdo->prepare("DELETE FROM `$pantry_table` WHERE id = ?")
        ->execute([(int)$_POST['delete_id']]);
    redirect();
}

/* ============================================================
   ADD TO SHOPPING LIST
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {

    $stmt = $pdo->prepare("
        SELECT name, quantity, barcode 
        FROM `$pantry_table` 
        WHERE id = ?
    ");
    $stmt->execute([(int)$_POST['shop_id']]);
    $item = $stmt->fetch();

    if ($item) {
        $check = $pdo->prepare("SELECT COUNT(*) FROM `$shop_table` WHERE name = ?");
        $check->execute([$item['name']]);

        if (!$check->fetchColumn()) {
            $pdo->prepare("
                INSERT INTO `$shop_table` (name, quantity, barcode)
                VALUES (?, ?, ?)
            ")->execute([
                $item['name'],
                $item['quantity'],
                $item['barcode']
            ]);
        }
    }
    redirect();
}



function getDateClass($date) {
    if (!$date) return '';
    $today = new DateTime();
    $target = new DateTime($date);
    $diff = (int)$today->diff($target)->format('%r%a'); 
    
    if ($diff < -5) return 'expired-dark';   // > 5 days expired
    if ($diff < 0)  return 'expired';        // expired within 5 days
    if ($diff < 7) return 'expiring';       // within 7 days

    return 'normal';
}

/* ============================================================
   QUICK UPDATE (SCAN AGAIN)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_update_item'])) {

    $pdo->prepare("
        UPDATE `$pantry_table`
        SET quantity = quantity + 1,
            expiration_date = ?
        WHERE barcode = ? OR id = ?
    ")->execute([
        $_POST['update_expiration_date'],
        $_POST['update_barcode'],
        $_POST['update_barcode']
    ]);

    redirect();
}

/* ============================================================
   ADD ITEM
============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {

    $name     = trim($_POST['name']);
    $exp      = trim($_POST['expiration_date']);
    $open      = trim($_POST['open_date']);
    $location = trim($_POST['location']);
    $category = trim($_POST['category']);
    $qty      = (int)$_POST['quantity'];
    $barcode  = trim($_POST['barcode'] ?? '') ?: null;

    if (!$name || !$exp || !$location || !$category || $qty <= 0) {
        echo "<script>alert('Fill all required fields');</script>";
    } else {

        $today = date('Y-m-d');

        if ($barcode) {

            // Save history (UPDATED: category instead of dairy)
            $pdo->prepare("
                INSERT IGNORE INTO food_history (barcode, name, category)
                VALUES (?, ?, ?)
            ")->execute([$barcode, $name, $category]);

            // Insert or update
            $pdo->prepare("
                INSERT INTO `$pantry_table`
                (id, name, expiration_date, open_date, location, category, quantity, barcode)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
            ")->execute([
                $barcode,
                $name,
                $exp,
                $open,
                $location,
                $category,
                $qty,
                $barcode
            ]);

        } else {
            $pdo->prepare("
                INSERT INTO `$pantry_table`
                (name, expiration_date, open_date, location, category, quantity, barcode)
                VALUES (?, ?, ?, ?, ?, ?, NULL)
            ")->execute([
                $name,
                $exp,
                $open,
                $location,
                $category,
                $qty
            ]);
        }

        redirect();
    }
}

/* ============================================================
   FETCH PANTRY
============================================================ */
$stmt = $pdo->query("
    SELECT id, name, quantity, expiration_date, open_date, location, category
    FROM `$pantry_table`
    ORDER BY $orderBy
");
$pantry_items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantry - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="favicon-32x32.png">

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
                    <img src="PantryPilotlogo2.png" style="height: 50px;">
                </a>
                <a href="index.php">Home</a>
                <a href="tracking.php" class="active">Pantry</a>
                <a href="grocery.php">Shopping List</a>
                <a href="about.php">About & Help</a>
            </div>
             <div class="nav-right">
                <a href="AccountInfo.php">Account Info</a>
                <a href="login.php" class="btn-login">Login</a>
                <a href="delete.php" class="btn-delete">Delete</a>
            </div>
            <div class="nav-end">
                <a href="settings.php" class="btn-settings"><i class="fa-solid fa-gear" style=" color: rgb(141, 141, 141)"></i></a>
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
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:10px; font-size:0.9rem;">
                    <span><span class="normal">●</span> Normal</span>
                    <span><span class="expiring">●</span> Expiring ≤ 7 days</span>
                    <span><span class="expired">●</span> Expired ≤ 5 days</span>
                    <span><span class="expired-dark">●</span> Expired > 5 days</span>
                </div>
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Expiration</th>
                                <th>Expiration When Opened</th>
                                <th>Location</th>
                                <th>Category</th>
                                <th class="category-row" colspan="1"> Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($pantry_items)): ?>
                                <?php foreach ($pantry_items as $item): ?>
                                    <tr>
                                        <td class="text-center"><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                                        <td class="<?= getDateClass($item['expiration_date']) ?>"><?= htmlspecialchars($item['expiration_date']) ?></td>
                                        <td class="<?= getDateClass($item['open_date']) ?>"><?= htmlspecialchars($item['open_date'] ?? '-') ?></td>
                                        <td class="text-center"><span class="badge badge-location"><?= htmlspecialchars($item['location']) ?></span></td>
                                        <td class="text-center"><span class="badge badge-location"><?= htmlspecialchars($item['category'] ?? '-') ?></span></td>
                                        <td class="text-center"><span class="badge badge-qty"><?= htmlspecialchars($item['quantity']) ?></span></td>
                                        <td class="action-cell">
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-action btn-danger" title="Remove Item"><i class="fa-solid fa-trash-can"></i> Remove</button>
                                            </form>
                                        </td>
                                        <td class="action-cell">
                                            <form method="POST" action="" class="inline-form" colspan="2">
                                                <input type="hidden" name="shop_id" value="<?= $item['id'] ?>">
                                                <button type="submit" class="btn-action btn-warning" title="Add to Shopping List"><i class="fa-solid fa-cart-plus"></i> Shop</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted"><br>No items found in pantry.<br>Use the form on the right to add some!<br><br></td></tr>
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
                            <label><i class="fa-solid fa-calendar-alt"></i> Expiration Date When Opened</label>
                            <input type="date" name="open_date" placeholder="Optional: Only If have an open date this should be filled.">
                        </div>
                        
                        <div class="input-group">
                            <label><i class="fa-solid fa-map-marker-alt"></i> Location</label>
                            <input type="text" name="location" required placeholder="e.g. Bottom Shelf">
                        </div>
                        
                        <div class="grid-2-col">
                            <div class="input-group">
                                <label><i class="fa-solid fa-shapes"></i> Category</label>
                                <input type="text" name="category" placeholder="Optional">
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

                <!-- Table Sort Section -->
                <div class="sort-section card-modern mt-4">
                <form method="GET" id="sortForm">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-puzzle-piece"></i> Sort Table</h2>
                    </div>
                      <div class="button-group">
                         <label><i class="fa-solid fa-tag"></i> NAME (A-Z)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="name_asc"  onchange="this.form.submit()"
                         <?= ($sort === 'name_asc') ? 'checked' : '' ?>>
                      </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-tag"></i> NAME (Z-A)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="name_desc"  onchange="this.form.submit()"
                         <?= ($sort === 'name_desc') ? 'checked' : '' ?>>
                      </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-layer-group"></i> QTY (High->Low)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="qty_desc" onchange="this.form.submit()"
                         <?= ($sort === 'qty_desc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-layer-group"></i> QTY (Low->High)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="qty_asc" onchange="this.form.submit()"
                         <?= ($sort === 'qty_asc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-calendar-alt"></i> EXPIRATION (Least RECENT)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="exp_desc" onchange="this.form.submit()"
                         <?= ($sort === 'exp_desc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-calendar-alt"></i> EXPIRATION (Most RECENT)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="exp_asc" onchange="this.form.submit()"
                         <?= ($sort === 'exp_asc') ? 'checked' : '' ?>>
                    </div>
                     <div class="button-group">
                         <label><i class="fa-solid fa-calendar-alt"></i> OPENDATE (Least RECENT)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="open_desc" onchange="this.form.submit()"
                         <?= ($sort === 'open_desc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-calendar-alt"></i> OPENDATE (Most RECENT)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="open_asc" onchange="this.form.submit()"
                         <?= ($sort === 'open_asc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-map-marker-alt"></i> LOCATION (A-Z)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="loc_asc" onchange="this.form.submit()"
                         <?= ($sort === 'loc_asc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-map-marker-alt"></i> LOCATION (Z-A)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="loc_desc" onchange="this.form.submit()"
                         <?= ($sort === 'loc_desc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-shapes"></i> CATEGORY (A-Z)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="cat_asc" onchange="this.form.submit()"
                         <?= ($sort === 'cat_asc') ? 'checked' : '' ?>>
                    </div>
                     <div class="button-group">
                         <label><i class="fa-solid fa-shapes"></i> CATEGORY (Z-A)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="cat_desc" onchange="this.form.submit()"
                         <?= ($sort === 'cat_desc') ? 'checked' : '' ?>>
                    </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-paper-plane"></i> WHEN ADDED (DEFAULT)</label>
                         <input type="radio" name="sort_table" class="cookie-btn" value="when_add" onchange="this.form.submit()"
                         <?= ($sort === 'when_add') ? 'checked' : '' ?>>
                    </div>
                </form>
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

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="barcode_scanner.js?v=1.1"></script>
</body>
</html>
