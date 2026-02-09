<?php
session_start();

// DB connection
$host = "localhost";
$user = "root";
$pass = "mysql";
$db   = "pantry";

//creates connection
$conn = new mysqli($host, $user, $pass, $db);

//checks connection
if ($conn->connect_error) {
    die("DB Connection Failed: " . $conn->connect_error);
}

$messageLogin = "";
$messageRegister = "";

// LOGIN
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT user_id, password FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $hash);
    $stmt->fetch();

    if ($stmt->num_rows > 0 && password_verify($password, $hash)) {
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = $id;

        // pantry table name (kept from your original logic)
        $cleanUsername = preg_replace("/[^a-zA-Z0-9]/", "_", strtolower($username));
        $_SESSION['pantry_table'] = $cleanUsername . "_pantry";

        header("Location: AccountInfo.php");
        exit;
    } else {
        $messageLogin = "Invalid username or password.";
    }

    $stmt->close();
}

// REGISTER
if (isset($_POST['register'])) {
    $username  = ($_POST['r_username']);
    $password  = ($_POST['r_password']);
    $email     = ($_POST['r_email']);
    $full_name = ($_POST['r_fullname']);
    $phone     = ($_POST['r_phone']);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    // store username's pantry DB name (you had this; kept for consistency)
    $dbName = preg_replace("/[^a-zA-Z0-9_]/", "", $username) . "_pantry";

    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hash, $email, $full_name, $phone);

    if ($stmt->execute()) {
        $pantryTable = $username . "_pantry";

        $createSql = "CREATE TABLE $pantryTable LIKE foods";
        if ($conn->query($createSql) === TRUE) {
            $messageRegister = "Account created and pantry is ready! Log in to begin.";
        } else {
            $messageRegister = "User created, but error creating pantry: " . $conn->error;
        }
    } else {
        $messageRegister = "Error: Username may already exist.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login/Register - Pantry Pilot</title>
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

        /* ---------- HEADER & TOP NAV (MATCHES INDEX HEADER) ---------- */
        .header-container {
            background-color: #ffcf33;
            border-bottom: 8px solid #ffcf33;
            position: relative;
            overflow: hidden;
        }

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


        /* Rotating header background (same as index.php) */
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
            background: rgba(255,255,255,0.30);  /* same opacity as index */
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

        /* ---------- MAIN LAYOUT ---------- */
        .form-row {
            width: 85%;
            margin: 50px auto 60px auto;
            display: flex;
            justify-content: space-between;
            gap: 30px;
        }

        .form-box {
            flex: 1;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        }

        .form-box h2 {
            text-align: center;
            color: #0b610b;
            font-style: italic;
            margin-top: 0;
            margin-bottom: 20px;
        }

        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #aaa;
            border-radius: 6px;
            font-size: 16px;
        }

        .submit-btn {
            background-color: #0b610b;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            width: 100%;
            font-size: 18px;
            cursor: pointer;
            font-weight: 600;
        }

        .submit-btn:hover {
            background-color: #0d7a0d;
        }

        p.error {
            color: red;
            font-weight: bold;
            margin-top: 10px;
        }

        p.success {
            color: green;
            font-weight: bold;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<div class="header-container">
    <!-- TOP NAVIGATION LINKS (same structure as index/tracking) -->
    <div class="top-nav">
        <div class="nav-left">
            <a href="index.php">Home</a>
            <a href="tracking.php">Pantry</a>
            <a href="grocery.php">Shopping List</a>
        </div>
        <div class="nav-right">
            <a href="AccountInfo.php"> Account Info</a>
            <a href="login.php", style="color: #145214; text-decoration: underline;">Login</a>
        </div>
    </div>

    <header>
        <!-- Rotating background images (exactly like index) -->
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

<div class="form-row">
    <!-- LOGIN -->
    <div class="form-box">
        <h2>Login</h2>

        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>

            <button type="submit" class="submit-btn" name="login">Log In</button>
        </form>

        <?php if ($messageLogin): ?>
            <p class="error"><?= htmlspecialchars($messageLogin) ?></p>
        <?php endif; ?>
    </div>

    <!-- REGISTER -->
    <div class="form-box">
        <h2>Create Account</h2>

        <form method="POST">
            <input type="text" name="r_username" placeholder="Username" required>
            <input type="password" name="r_password" placeholder="Password" required>
            <input type="text" name="r_fullname" placeholder="Full Name" required>
            <input type="email" name="r_email" placeholder="Email" required>
            <input type="text" name="r_phone" placeholder="Phone Number" required>

            <button type="submit" class="submit-btn" name="register">Register</button>
        </form>

        <?php if ($messageRegister): ?>
            <p class="success"><?= htmlspecialchars($messageRegister) ?></p>
        <?php endif; ?>
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
