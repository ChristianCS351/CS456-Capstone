<?php
session_start();

// Prevent access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "mysql", "pantry");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// User-specific tables from session
$pantryTable = $_SESSION['pantry_table'];
$shopTable   = $_SESSION['shop_table']; // <-- make sure this is set at login/registration

/* ------------------ DELETE ITEM FROM PANTRY ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];

    $stmt = $conn->prepare("DELETE FROM `$pantryTable` WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ------------------ ADD PANTRY ITEM TO SHOPPING LIST ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['shop_id'])) {
    $shop_id = (int)$_POST['shop_id'];

    // Get item from pantry
    $stmt = $conn->prepare("SELECT name, quantity, barcode FROM `$pantryTable` WHERE id = ?");
    $stmt->bind_param("i", $shop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $stmt->close();

    if ($item) {
        // Check if item already exists in user's shop table
        $check = $conn->prepare("SELECT COUNT(*) as count FROM `$shopTable` WHERE name = ?");
        $check->bind_param("s", $item['name']);
        $check->execute();
        $checkResult = $check->get_result();
        $existsRow = $checkResult->fetch_assoc();
        $check->close();

        if ($existsRow['count'] == 0) {
            $stmt2 = $conn->prepare("INSERT INTO `$shopTable` (name, quantity, barcode) VALUES (?, ?, ?)");
            $stmt2->bind_param("sis", $item['name'], $item['quantity'], $item['barcode']);
            $stmt2->execute();
            $stmt2->close();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* ------------------ INSERT NEW ITEM INTO PANTRY ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    $name = trim($_POST['name']);
    $expiration_date = trim($_POST['expiration_date']);
    $location = trim($_POST['location']);
    $dairy = $_POST['dairy'] ?? 'N';
        if (!in_array($dairy, ['Y','N'])) {
            $dairy = 'N'; //default to N if not set
        }
    $quantity = (int)$_POST['quantity'];
    $barcode = isset($_POST['barcode']) && $_POST['barcode'] !== '' ? $_POST['barcode'] : null;

    if (empty($name) || empty($expiration_date) || empty($location) || $quantity <= 0) {
        echo "<script>alert('Please fill in all required fields correctly before submitting.');</script>";
    } else {
        $today = date('Y-m-d');
        $open_exp = date('Y-m-d', strtotime('+1 year'));

    $stmt = $conn->prepare("
        INSERT INTO `$pantryTable`
        (name, expiration_date, quantity, location, dairy, barcode)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "ssisss",
        $name,
        $expiration_date,
        $quantity,
        $location,
        $dairy,
        $barcode
    );

    $stmt->execute();
    $stmt->close();

        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

/* ------------------ FETCH PANTRY ITEMS ------------------ */
$pantry_items = [];
$sql = "SELECT id, name, quantity, expiration_date, location FROM `$pantryTable`";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pantry_items[] = $row;
    }
}

/* ------------------ FETCH SHOPPING LIST ITEMS ------------------ */
$shop_items = [];
$sql2 = "SELECT id, name, quantity, barcode FROM `$shopTable` ORDER BY id ASC";
$result2 = $conn->query($sql2);

if ($result2 && $result2->num_rows > 0) {
    while ($row = $result2->fetch_assoc()) {
        $shop_items[] = $row;
    }
}
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

    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #fff9ebff;
            color: #222;
        }

        .header-container {
            background-color: #ffcf33;
            border-bottom: 8px solid #ffcf33;
            position: relative;
            overflow: hidden;
        }

        /* ---------- TOP NAVIGATION (EXACTLY LIKE INDEX) ---------- */
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 25px;
            background-color: #ffcf33;
        }

        .top-nav .nav-left a,
        .top-nav .nav-right a {
            margin-right: 20px;
            color: #1b5e20;
            font-style: italic;
            font-size: 17px;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease, text-decoration 0.2s ease;
            cursor: pointer;
        }

        .top-nav .nav-right a:last-child {
            margin-right: 0;
        }

        .top-nav a:hover {
            color: #145214;
            text-decoration: underline;
        }

        /* ---------- HEADER WITH ROTATING BACKGROUND (COPY FROM INDEX) ---------- */
        header {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .hero-slide img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        header::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.30);
            z-index: 1;
        }

        header img {
            position: relative;
            z-index: 2;
            height: 190px;
            object-fit: contain;
        }

        /* This enlarges the logo when the pointer is on it */
        header :hover img {
            transform: scale(1.08);
        }

        /* ---------- TRACKING PAGE LAYOUT ---------- */
        .main-content {
            max-width: 1100px;
            margin: 40px auto;
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .pantry-section,
        .form-section {
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
            flex: 1;
        }

        .pantry-section {
            flex: 1.2;
        }

        .pantry-section h2,
        .form-section h2 {
            color: #1b5e20;
            text-align: center;
            border-bottom: 3px solid #ffcf33;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #1b5e20;
            color: white;
            padding: 10px;
            text-align: left;
        }

        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            background-color: #f7f7f7;
        }

        tr:hover td {
            background-color: #e8f5e9;
        }

        .delete-btn, .shop-btn {
            border: none;
            padding: 6px 12px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.2s;
            color: white;
            border-radius: 4px;
        }

        .delete-btn {
            background-color: #004b23;
        }

        .delete-btn:hover {
            background-color: #003017;
        }

        .shop-btn {
            background-color: #e5b600;
            color: #004b23;
        }

        .shop-btn:hover {
            background-color: #cfa400;
        }

        .form-section label {
            display: block;
            margin: 10px 0 4px;
            font-weight: 600;
        }

        .form-section input {
            width: 100%;
            padding: 8px;
            margin-bottom: 8px;
            border-radius: 6px;
            border: 1px solid #bbb;
        }

        .submit-btn, .scan-btn {
            display: inline-block;
            background-color: #e5b600;
            color: #004b23;
            border: 2px solid #004b23;
            font-weight: bold;
            padding: 10px 25px;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
            border-radius: 8px;
        }

        .submit-btn:hover, .scan-btn:hover {
            background-color: #004b23;
            color: #ffffff;
        }

        .scan-section {
            text-align: center;
            margin-top: 25px;
        }

        .scan-section h3 {
            color: #004b23;
        }
    </style>
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

            <label>Category (Dairy?):</label>
            <select name="dairy" required>
                <option value="N" selected>No</option>
                <option value="Y">Yes</option>
            </select>

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

<script>
$(document).ready(function(){
    $('.hero-slide').slick({
        slidesToShow: 1,
        slidesToScroll: 1,
        autoplay: true,
        infinite: true,
        autoplaySpeed: 5600,
        arrows: false,
        speed: 3800,
        fade: true,
        cssEase: 'linear'
    });
});
</script>

<!-- Barcode library -->
<script src="https://unpkg.com/html5-qrcode"></script>

<!-- Your separate JS file -->
<script src="barcode_scanner.js"></script>

</body>
</html>
