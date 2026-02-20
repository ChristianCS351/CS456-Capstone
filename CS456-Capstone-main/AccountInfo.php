<?php 
session_start();

// Prevent access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login_register.php");
    exit;
}

// DB connection
$conn = new mysqli("localhost", "root", "mysql", "pantry");

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT username, email, full_name, phone FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$userInfo = $result->fetch_assoc();
$stmt->close();

$pantryTable = $_SESSION['pantry_table'];

// items expiring within the next 7 days (including today)
$expiringItems = [];

$sql = "SELECT name, expiration_date
        FROM `$pantryTable`
        WHERE expiration_date >= CURDATE()
          AND expiration_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY expiration_date ASC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $expiringItems[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Info - Pantry Pilot</title>
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

        /* ---------- TOP NAVIGATION (same as index) ---------- */
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

        /* ---------- HEADER WITH ROTATING BACKGROUND (EXACT COPY FROM INDEX) ---------- */
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

        header a {
            display: inline-block;
        }

        /* ---------- MAIN CONTENT LAYOUT (same as you had) ---------- */
        main {
            max-width: 1100px;
            margin: 40px auto 60px auto;
            padding: 0 20px 40px 20px;
        }

        .content-row {
            display: flex;
            flex-wrap: wrap;
            gap: 30px;
        }

        .container, .pantry-box {
            flex: 1 1 320px;
            background-color: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }

        h2 {
            text-align: center;
            font-size: 24px;
            margin-bottom: 20px;
            color: #0b610b;
            border-bottom: 3px solid #ffcf33;
            padding-bottom: 8px;
            font-style: italic;
        }

        .info p {
            font-size: 18px;
            padding: 8px 0;
            border-bottom: 1px solid #ddd;
        }

        .logout-btn {
            width: 100%;
            padding: 12px;
            background-color: #0b610b;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            margin-top: 20px;
            transition: background-color .2s;
        }

        .logout-btn:hover {
            background-color: #0d7a0d;
        }

        .pantry-box p {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .pantry-box ul {
            font-size: 18px;
            line-height: 1.6;
            padding-left: 20px;
        }
    </style>
</head>

<body>

<div class="header-container">
    <!-- TOP NAVIGATION LINKS -->
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="tracking.php">Pantry</a>
            <a href="grocery.php">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php", style="color: #145214; text-decoration: underline;">Account Info</a>
            <a href="login.php">Login</a>
        </div>
    </div>

    <!-- ROTATING HEADER (EXACTLY LIKE INDEX) -->
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

<main>
    <div class="content-row">

        <!-- account info -->
        <div class="container">
            <h2>Account Information</h2>

            <div class="info">
                <p><strong>Username:</strong> <?php echo htmlspecialchars($userInfo['username']); ?></p>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($userInfo['full_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($userInfo['email']); ?></p>
                <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($userInfo['phone']); ?></p>
            </div>

            <a href="login.php">
                <button class="logout-btn">Logout</button>
            </a>
        </div>

        <!-- pantry items that are about to expire -->
        <div class="pantry-box">
            <h2>Pantry Items</h2>

            <p>These items are about to expire:</p>

            <ul>
                <?php if (empty($expiringItems)) : ?>
                    <li>No items expiring soon!</li>
                <?php else : ?>
                    <?php foreach ($expiringItems as $item): ?>
                        <li>
                            <?php echo htmlspecialchars($item['name']); ?> — Expires: <?php echo htmlspecialchars($item['expiration_date']); ?>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>

        </div>

    </div>
</main>

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
