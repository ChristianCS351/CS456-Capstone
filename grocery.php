<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "mysql";
$db   = "pantry";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

$shop_table = isset($_SESSION['shop_table']) ? $_SESSION['shop_table'] : 'shop_list';

$messageList = "";

/* ------------------ CLEAR LIST (DELETE ALL) ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_list'])) {
    $conn->query("DELETE FROM `$shop_table`");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// INSERT ITEM INTO shop_list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['list'])) {
    $name     = trim($_POST['item_name']);
    $quantity = (int)$_POST['qty'];

    if ($name !== "" && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO `$shop_table` (name, quantity) VALUES (?, ?)");
        $stmt->bind_param("si", $name, $quantity);

        if ($stmt->execute()) {
            $messageList = "Item added to shopping list.";
        } else {
            $messageList = "Error adding item.";
        }

        $stmt->close();
    } else {
        $messageList = "Please enter a valid item name and quantity.";
    }
}

/* ------------------ DELETE SINGLE ITEM ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $conn->prepare("DELETE FROM `$shop_table` WHERE id = ?");
    $stmt->bind_param("i", $_POST['delete_id']);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// FETCH CURRENT SHOPPING LIST
$listItems = [];
// $result = $conn->query("SELECT id, name, quantity FROM `$shop_table` ORDER BY id ASC");
$sort = isset($_GET['sort_table']) ? $_GET['sort_table'] : 'name_asc';

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
     default:
        $orderBy = "name ASC";
       
        break;
}
$result = $conn->query("SELECT id, name, quantity FROM $shop_table ORDER BY $orderBy");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $listItems[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping List - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="favicon-32x32.png">

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="grocery.css">
</head>
<body class="grocery-page">


    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="nav-brand">
                    <img src="PantryPilotlogo2.png" style="height: 50px;">
                </a>
                <a href="index.php">Home</a>
                <a href="tracking.php">Pantry</a>
                <a href="grocery.php" class="active">Shopping List</a>
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
            <h1><i class="fa-solid fa-cart-shopping"></i> Shopping List</h1>
            <p>Plan your next grocery trip, save items, and print your list seamlessly.</p>
        </div>
    </header>

    <main class="main-content">
        <div class="split-layout">
            
            <!-- Left: Current Grocery List -->
            <section class="grocery-section card-modern" id="print_area">
                <div class="card-header d-flex-between">
                    <h2><i class="fa-solid fa-clipboard-list"></i> Current List</h2>
                    <div class="print-hide">
                        <span class="badge badge-qty"><?= count($listItems) ?> Items</span>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>QTY</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($listItems)): ?>
                                <?php foreach ($listItems as $row): ?>
                                    <tr>
                                        <td class="text-center"><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                                        <td class="text-center"><span class="badge badge-location"><?= htmlspecialchars($row['quantity']) ?></span></td>
                                        <td class="action-cell">
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="delete_id" value="<?= $row['id'] ?>">
                                                <button type="submit" class="btn-action btn-danger"><i class="fa-solid fa-trash-can"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="2" class="text-center text-muted"><br>No items in your shopping list yet.<br><br></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card-footer action-buttons">
                    <button type="button" class="btn-actions btn-success"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    <button type="button" class="btn-actions btn-primary" onclick="handlePrint()"><i class="fa-solid fa-print"></i> Print</button>
                </div>

                <!-- HIDDEN FORM USED TO CLEAR LIST AFTER PRINT -->
                <form method="POST" id="clear_form" style="display:none;">
                    <input type="hidden" name="clear_list" value="1">
                </form>
            </section>

            <!-- Right: Add an Item -->
            <aside class="sidebar-section">
                <!-- Add Item Form -->
                <div class="form-section card-modern">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-plus-circle"></i> Add an Item</h2>
                    </div>
                    <form method="POST" action="" class="modern-form">
                        <div class="input-group">
                            <label><i class="fa-solid fa-tag"></i> Item Name</label>
                            <input type="text" name="item_name" required placeholder="e.g. Milk">
                        </div>
                        
                        <div class="input-group">
                            <label><i class="fa-solid fa-layer-group"></i> Quantity</label>
                            <input type="number" name="qty" min="1" required value="1">
                        </div>

                        <input type="hidden" name="barcode" id="barcode">
                        
                        <button type="submit" name="list" class="btn-submit">
                            <i class="fa-solid fa-check"></i> Add Item
                        </button>
                    </form>

                    <?php if (!empty($messageList)): ?>
                        <div class="alert message-success text-center" style="margin: 0 24px 24px;">
                            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($messageList) ?>
                        </div>
                    <?php endif; ?>
                </div>


                <!-- Barcode Scanner -->
                <div class="scan-section card-modern mt-4">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-barcode"></i> Quick Scan</h2>
                    </div>
                    <div class="scan-body text-center">
                        <p class="text-muted mb-3">Scan an empty product to add it straight to your list.</p>
                        <button type="button" class="btn-scan" id="scan_btn"><i class="fa-solid fa-camera"></i> Scan Barcode</button>
                        <div id="reader" style="width:100%; max-width:100%; margin:15px auto; display:none; border-radius: 8px; overflow: hidden;"></div>
                        <p id="scan_result" class="scan-result-text"></p>
                    </div>    
                </div>
   
                 <div class="sort-section card-modern mt-4">
                 <form method="GET" id="sortForm">
                    <div class="card-header">
                        <h2><i class="fa-solid fa-puzzle-piece"></i> Sort Table</h2>
                    </div>
                      <div class="button-group">
                         <label><i class="fa-solid fa-tag"></i> NAME (A-Z) (DEFAULT)</label>
                         <input type="radio"  name="sort_table" class="cookie-btn" value="name_asc" onchange="this.form.submit()"
                         <?= ($sort === 'name_asc') ? 'checked' : '' ?>>
                      </div>
                    <div class="button-group">
                         <label><i class="fa-solid fa-tag"></i> NAME (Z-A)</label>
                         <input type="radio"  name="sort_table" class="cookie-btn" value="name_desc" onchange="this.form.submit()"
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
                  </form>
                  </div>
            </aside>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="barcode_scanner.js?v=1"></script>
    <script src="grocery.js"></script>
</body>
</html>
