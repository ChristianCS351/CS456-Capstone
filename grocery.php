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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pantry Pilot - Shopping List</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Slick slider CSS (same as index header) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* ---------- HEADER / GLOBAL (MATCH TRACKING/INDEX) ---------- */
        body {
            margin: 0;
            padding: 0;
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #f9f9f9;
            color: #222;
        }

        .header-container {
            background-color: #ffcf33;
            border-bottom: 10px solid #ffcf33;
            position: relative;
            overflow: hidden;
        }

        /* TOP NAV EXACTLY LIKE OTHER PAGES */
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

        /* ---------- HEADER WITH ROTATING BACKGROUND (EXACTLY LIKE INDEX) ---------- */
        header {
            position: relative;
            width: 100%;
            height: 300px;
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

        header a {
            display: inline-block;
        }

        /* ---------- MAIN LAYOUT ---------- */
        .main-content {
            max-width: 1100px;
            margin: 40px auto;
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .grocery-section {
            flex: 1.2;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
        }

        .add-section {
            flex: 1;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
        }

        .grocery-section h2,
        .add-section h2 {
            text-align: center;
            color: #1b5e20;
            margin-bottom: 15px;
            border-bottom: 3px solid #ffcf33;
            padding-bottom: 8px;
        }

        /* ---------- TABLE ---------- */
        table {
            border-collapse: collapse;
            width: 100%;
            text-align: center;
        }

        th, td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
        }

        th {
            background-color: #1b5e20;
            color: white;
            font-weight: bold;
        }

        tr:nth-child(even) td {
            background-color: #f7f7f7;
        }

        tr:hover td {
            background-color: #e8f5e9;
        }

        /* ---------- BUTTONS ---------- */
        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .save-btn,
        .print-btn,
        .submit-btn,
        .scan-btn {
            display: inline-block;
            background-color: #e5b600;
            color: #004b23;
            border: 2px solid #004b23;
            font-weight: bold;
            padding: 8px 18px;
            cursor: pointer;
            transition: 0.2s;
            text-align: center;
            border-radius: 8px;
        }

        .save-btn:hover,
        .print-btn:hover,
        .submit-btn:hover,
        .scan-btn:hover {
            background-color: #004b23;
            color: white;
        }

        /* ---------- FORM ---------- */
        .add-section label {
            display: block;
            margin: 10px 0 5px;
            font-weight: 600;
        }

        .add-section input {
            width: 100%;
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #bbb;
            margin-bottom: 8px;
        }

        .submit-wrapper {
            text-align: center;
            margin-top: 10px;
        }

        .scan-section {
            text-align: center;
            margin-top: 30px;
        }

        .scan-section h3 {
            color: #004b23;
            font-style: italic;
        }

        .success {
            margin-top: 10px;
            color: #004b23;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="header-container">
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="tracking.php">Pantry</a>
            <a href="grocery.php">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php">Account Info</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <header>
        <!-- Rotating background images (same order as index) -->
        <div class="hero-slide">
            <div>
                <img src="pasta.jpg" alt="Jars of Pasta">
            </div>
            <div>
                <img src="frozen-foods-displayed-supermarket-freezer-section_641503-100271.avif" alt="Freezers with Food">
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
        </div>

        <!-- Center logo (clickable) -->
        <a href="index.php">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo">
        </a>
    </header>
</div>

<div class="main-content">

    <!-- LEFT: CURRENT GROCERY LIST -->
    <div class="grocery-section">
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
            <button type="button" class="print-btn" onclick="window.print()">Print</button>
        </div>
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
        </form>

        <?php if (!empty($messageList)): ?>
            <p class="success"><?= htmlspecialchars($messageList) ?></p>
        <?php endif; ?>

        <div class="scan-section">
            <h3>OR SCAN BARCODE</h3>
            <button type="button" class="scan-btn">SCAN</button>
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

</body>
</html>
