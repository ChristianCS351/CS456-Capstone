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

    // Get item info from foods table
    $stmt = $pdo->prepare("SELECT name, quantity, barcode FROM foods WHERE id = :id");
    $stmt->execute([':id' => $shop_id]);
    $item = $stmt->fetch();

    if ($item) {
        // Check if it already exists in shop_list
        $check = $pdo->prepare("SELECT COUNT(*) FROM shop_list WHERE name = :name");
        $check->execute([':name' => $item['name']]);
        $exists = $check->fetchColumn();

        if ($exists == 0) {
            $stmt2 = $pdo->prepare("
                INSERT INTO shop_list (name, quantity, barcode)
                VALUES (:name, :quantity, :barcode)
            ");
            $stmt2->execute([
                ':name'     => $item['name'],
                ':quantity' => $item['quantity'],
                ':barcode'  => $item['barcode']   // can be null, that's ok
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

    // NEW: barcode coming from scanner (hidden input)
    $barcode = isset($_POST['barcode']) ? trim($_POST['barcode']) : null;
    if ($barcode === '') { $barcode = null; }

    if (empty($name) || empty($expiration_date) || empty($location) || $quantity <= 0) {
        echo "<script>alert('Please fill in all required fields correctly before submitting.');</script>";
    } else {
        $today = date('Y-m-d');

        $stmt = $pdo->prepare("INSERT INTO foods 
            (name, expiration_date, quantity, location, dairy, open_date, open_expiration_date, barcode)
            VALUES (:name, :expiration_date, :quantity, :location, :dairy, :open_date, :open_expiration_date, :barcode)");
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
    <title>Pantry - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Slick slider CSS (same as index header) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    
    <link rel="stylesheet" href="tracking.css">
</head>
<body>

<div class="header-container">

    <!-- TOP NAVIGATION LINKS (same as index) -->
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="tracking.php", style="color: #145214; text-decoration: underline;">Pantry</a>
            <a href="grocery.php">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php">Account Info</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <!-- ROTATING HEADER (copied from index) -->
    <header>
        <div class="hero-slide">
            <div>
                <img src="pasta.jpg" alt="Jars of Pasta">
            </div>
            <div>
                <img src="frozen-food.avif" alt= "Freezers with Food">
            </div>
            <div>
                <img src="OIP.webp" alt="Fruit Stacked">
            </div>
            <div>
                <img src="00-FOOD-PANTRIES-CLOSING-SAVEUR.webp" alt="Various Pantry Foods">
            </div>
            <div>
                <img src="produce-vegetables.jpg" alt="Fresh Produce">
            </div>
            <div>
                <img src="pantry-stuff.webp" alt="Jars and Juices on Shelves">
            </div>
        </div>

        <a href="index.php">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo">
        </a>
    </header>
</div>

<div class="main-content">

    <div class="pantry-section">
        <h2>MY PANTRY</h2>
        <table>
            <tr>
                <th>Name</th>
                <th>QTY</th>
                <th>Expiration</th>
                <th>Location</th>
                <th colspan="2">Actions</th>
            </tr>

            <?php if (!empty($pantry_items)): ?>
                <?php foreach ($pantry_items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td><?= htmlspecialchars($item['expiration_date']) ?></td>
                        <td><?= htmlspecialchars($item['location']) ?></td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="delete_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="delete-btn">Remove</button>
                            </form>
                        </td>
                        <td>
                            <form method="POST" action="" style="display:inline;">
                                <input type="hidden" name="shop_id" value="<?= $item['id'] ?>">
                                <button type="submit" class="shop-btn">+ Shopping</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No items found in pantry.</td></tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="form-section">
        <h2>ADD AN ITEM</h2>
        <form method="POST" action="" id="add_item_form">
            <label>Name:</label>
            <input type="text" name="name" required>

            <label>Expiration:</label>
            <input type="date" name="expiration_date" required>

            <label>Location:</label>
            <input type="text" name="location" required>

            <label>Category:</label>
            <input type="text" name="dairy">

            <label>QTY:</label>
            <input type="number" name="quantity" min="1" required>

            <!-- NEW: barcode from scanner (hidden) -->
            <input type="hidden" name="barcode" id="barcode">

            <div style="text-align:center; margin-top:10px;">
                <button type="submit" name="add_item" class="submit-btn">SUBMIT</button>
            </div>
        </form>

        <div class="scan-section">
            <h3>OR SCAN BARCODE</h3>

            <button type="button" class="scan-btn" id="scan_btn">SCAN</button>

            <div id="reader" style="width:100%; max-width:420px; margin:15px auto; display:none;"></div>

            <p id="scan_result" style="font-weight:600; color:#004b23;"></p>
        </div>
    </div>
</div>

<!-- Slick slider JS (same as index) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/3.5.2/jquery-migrate.min.js" integrity="sha512-BzvgYEoHXuphX+g7B/laemJGYFdrq4fTKEo+B3PurSxstMZtwu28FHkPKXu6dSBCzbUWqz/rMv755nUwhjQypw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js" integrity="sha512-HGOnQO9+SP1V92SrtZfjqxxtLmVzqZpjFFekvzZVWoiASSQgSr4cw9Kqd2+l8Llp4Gm0G8GIFJ4ddwZilcdb8A==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<!-- Barcode library -->
<script src="https://unpkg.com/html5-qrcode"></script>

<!-- Your separate JS file -->
<script src="barcode_scanner.js"></script>

    <script src="app.js"></script>
</body>
</html>
