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
            ':barcode' => null
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
    <title>Pantry Pilot - Pantry</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">


    <!-- Slick slider CSS (same as index header) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css" integrity="sha512-yHknP1/AwR+yx26cB1y0cjvQUMvEa2PFzt1c9LlS4pRQ5NOTZFWbhBig+X9G9eYW/8m0/4OXNx8pxJ6z57x0dw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css" integrity="sha512-17EgCFERpgZKcm0j0fEq1YCJuyAWdz9KUtv1EjVuaOz8pDnh/0nZxmU6BBXwaaxqoi9PQXnRWqlcDB027hgv9A==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
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
            <a href="tracking.php">Pantry</a>
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

        <a href="index.php">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo">
        </a
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
        <form method="POST" action="">
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

            <div style="text-align:center; margin-top:10px;">
                <button type="submit" name="add_item" class="submit-btn">SUBMIT</button>
            </div>
        </form>

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
