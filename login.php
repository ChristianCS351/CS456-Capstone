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

    
    <link rel="stylesheet" href="login.css">
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

    <script src="app.js"></script>
</body>
</html>
