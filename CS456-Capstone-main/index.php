<?php
session_start();

//prevents access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

//DB connection
$conn = new mysqli("localhost", "root", "mysql", "pantry");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

//using tables created during login
$pantryTable = $_SESSION['pantry_table'];
$shopTable   = $_SESSION['shop_table'];


// ---------------------------------------------------------------------------
// EXPIRING SOON LIMIT (default 5 → expand to 10)
$exp_limit = 5;
if (isset($_GET['exp']) && $_GET['exp'] == 10) {
    $exp_limit = 10;
}

$expiringFoods = [];

$sql = "SELECT name, expiration_date, quantity
        FROM `$pantryTable`
        ORDER BY expiration_date ASC
        LIMIT ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $exp_limit);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $expiringFoods[] = $row;
}
$stmt->close();


// ---------------------------------------------------------------------------
// GROCERY LIST LIMIT (default 5 → expand to 10)
$g_limit = 5;
if (isset($_GET['gro']) && $_GET['gro'] == 10) {
    $g_limit = 10;
}

$groceryItems = [];

$sql2 = "SELECT name, quantity, barcode
         FROM `$shopTable`
         ORDER BY id ASC
         LIMIT ?";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $g_limit);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    $groceryItems[] = $row;
}
$stmt2->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Home - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Slick slider CSS (for rotating header background) -->
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

        /* ---------- TOP NAVIGATION ---------- */
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

        /* ---------- HEADER WITH ROTATING BACKGROUND ---------- */
        header {
            position: relative;
            width: 100%;
            height: 400px;
        overflow: hidden;
        display: flex;
        justify-content: center;
    align-items: center;
    }

    /* Make slider fill FULL width + height */
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

/* Make overlay LESS transparent (stronger white layer) */
header::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(255,255,255,0.30);   /* was 0.55 – now stronger */
    z-index: 1;
}

/* Logo stays above overlay */
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

        /* ---------- MAIN CONTENT ---------- */
        main {
            margin: 40px auto;
            max-width: 1000px;
            background-color: #ffffff;
            border-radius: 12px;
            padding: 30px 40px 45px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.12);
        }

        h2 {
            color: #1b5e20;
            text-align: center;
            border-bottom: 3px solid #ffcf33;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th {
            background-color: #1b5e20;
            color: white;
            padding: 12px;
            text-align: left;
            font-size: 17px;
        }

        td {
            background-color: #f7f7f7;
            padding: 12px;
            border-bottom: 1px solid #ddd;
        }

        tr:hover td {
            background-color: #e8f5e9;
        }

        section {
            margin-bottom: 50px;
        }

        body, h2, th, td, p {
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="header-container">
    <!-- TOP NAVIGATION LINKS -->
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php", style="color: #145214; text-decoration: underline;">Home</a>
            <a href="tracking.php">Pantry</a>
            <a href="grocery.php">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php">Account Info</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <header>
        <!-- Rotating background images -->
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

        <!-- Center logo (clickable) -->
        <a href="index.php">
            <img src="pantry_pilot_logo-removebg-preview.png" alt="Pantry Pilot Logo">
        </a>
    </header>
</div>

<main>

    <!-- EXPIRING SOON TABLE -->
    <section id="expiring-soon">
        <h2>EXPIRING SOON</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Expiration Date</th>
                <th>Quantity</th>
            </tr>
            <?php foreach ($expiringFoods as $food): ?>
                <tr>
                    <td><?= htmlspecialchars($food['name']) ?></td>
                    <td><?= htmlspecialchars($food['expiration_date']) ?></td>
                    <td><?= htmlspecialchars($food['quantity']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- SEE MORE / SEE LESS FOR EXPIRING SOON -->
        <div style="text-align:center; margin-top: 15px;">
            <?php if ($exp_limit == 5): ?>
                <a href="?exp=10&gro=<?= $g_limit ?>"
                   style="color:#1b5e20; font-weight:600; text-decoration:none;">
                    See More
                </a>
            <?php else: ?>
                <a href="?exp=5&gro=<?= $g_limit ?>"
                   style="color:#1b5e20; font-weight:600; text-decoration:none;">
                    See Less
                </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- CURRENT GROCERY LIST TABLE -->
    <section id="current-grocery">
        <h2>CURRENT GROCERY LIST</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>Barcode</th>
            </tr>
            <?php foreach ($groceryItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td><?= htmlspecialchars($item['quantity']) ?></td>
                    <td><?= htmlspecialchars($item['barcode']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- SEE MORE / SEE LESS BUTTON -->
        <div style="text-align:center; margin-top: 15px;">
            <?php if ($g_limit == 5): ?>
                <a href="?gro=10&exp=<?= $exp_limit ?>"
                   style="color:#1b5e20;font-weight:1000; text-decoration:none;">
                    See More
                </a>
            <?php else: ?>
                <a href="?gro=5&exp=<?= $exp_limit ?>"
                   style="color:#1b5e20;font-weight:1000; text-decoration:none;">
                    See Less
                </a>
            <?php endif; ?>
        </div>
    </section>

</main>

<!-- Slick slider JS -->
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
