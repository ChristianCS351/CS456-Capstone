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

$messageList = "";

/* ------------------ CLEAR LIST (DELETE ALL) ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_list'])) {
    $conn->query("DELETE FROM shop_list");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// INSERT ITEM INTO shop_list
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['list'])) {
    $name     = trim($_POST['item_name']);
    $quantity = (int)$_POST['qty'];

    if ($name !== "" && $quantity > 0) {
        $stmt = $conn->prepare("INSERT INTO shop_list (name, quantity) VALUES (?, ?)");
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

// FETCH CURRENT SHOPPING LIST
$listItems = [];
$result = $conn->query("SELECT id, name, quantity FROM shop_list ORDER BY id ASC");
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
    <title>Shopping List - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Slick slider CSS (same as index header) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    
    <link rel="stylesheet" href="grocery.css">
</head>
<body>

<div class="header-container">
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="tracking.php">Pantry</a>
            <a href="grocery.php", style="color: #145214; text-decoration: underline;">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php">Account Info</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <header>
        <!-- Rotating background images (same order as index) -->
        <div class="hero-slide">
            <div><img src="pasta.jpg" alt="Jars of Pasta"></div>
            <div><img src="frozen-food.avif" alt="Freezers with Food"></div>
            <div><img src="OIP.webp" alt="Fruit Stacked"></div>
            <div><img src="00-FOOD-PANTRIES-CLOSING-SAVEUR.webp" alt="Various Pantry Foods"></div>
            <div><img src="produce-vegetables.jpg" alt="Fresh Produce"></div>
            <div><img src="pantry-stuff.webp" alt="Jars and Juices on Shelves"></div>
        </div>

        <!-- Center logo (clickable) -->
        <a href="index.php">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo">
        </a>
    </header>
</div>

<div class="main-content">

    <!-- LEFT: CURRENT GROCERY LIST -->
    <div class="grocery-section" id="print_area">
        <h2>CURRENT GROCERY LIST</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>QTY</th>
            </tr>
            <?php if (!empty($listItems)): ?>
                <?php foreach ($listItems as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="2">No items in your shopping list yet.</td></tr>
            <?php endif; ?>
        </table>

        <div class="action-buttons">
            <button type="button" class="save-btn">Save</button>
            <button type="button" class="print-btn" onclick="handlePrint()">Print</button>
        </div>

        <!-- HIDDEN FORM USED TO CLEAR LIST AFTER PRINT -->
        <form method="POST" id="clear_form" style="display:none;">
            <input type="hidden" name="clear_list" value="1">
        </form>
    </div>

    <!-- RIGHT: ADD AN ITEM -->
    <div class="add-section">
        <h2>ADD AN ITEM</h2>
        <form method="POST" action="">
            <label>Item Name:</label>
            <input type="text" name="item_name" required>

            <label>Quantity:</label>
            <input type="number" name="qty" min="1" required>

            <div class="submit-wrapper">
                <button type="submit" name="list" class="submit-btn">SUBMIT</button>
            </div>

            <input type="hidden" name="barcode" id="barcode">
        </form>

        <?php if (!empty($messageList)): ?>
            <p class="success"><?= htmlspecialchars($messageList) ?></p>
        <?php endif; ?>

        <div class="scan-section">
            <h3>OR SCAN BARCODE</h3>

            <button type="button" class="scan-btn" id="scan_btn">SCAN</button>

            <div id="reader" style="width:100%; max-width:420px; margin:15px auto; display:none;"></div>

            <p id="scan_result" style="font-weight:600; color:#004b23;"></p>
        </div>
    </div>

</div>

<!-- Slick slider JS (same as index) -->
<!-- Barcode library -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="barcode_scanner.js?v=1"></script>

    <script src="app.js"></script>
    <script src="grocery.js"></script>
</body>
</html>
