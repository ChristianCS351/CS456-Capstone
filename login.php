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
        $cleanUsername = preg_replace("/[^a-zA-Z0-9_]/", "", strtolower($username));
        $_SESSION['pantry_table'] = $cleanUsername . "_pantry";
        $_SESSION['shop_table'] = $cleanUsername . "_shop";

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

    // store username's pantry DB name
    $dbName = preg_replace("/[^a-zA-Z0-9_]/", "", $username) . "_pantry";

    $stmt = $conn->prepare("INSERT INTO users (username, password, email, full_name, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $username, $hash, $email, $full_name, $phone);

    if ($stmt->execute()) {
        $cleanUsername = preg_replace("/[^a-zA-Z0-9_]/", "", strtolower($username));
        $pantryTable = $cleanUsername . "_pantry";
        $shopTable = $cleanUsername . "_shop";

        $createPantrySql = "CREATE TABLE IF NOT EXISTS `$pantryTable` LIKE foods";
        $createShopSql = "CREATE TABLE IF NOT EXISTS `$shopTable` LIKE shop_list";
        
        if ($conn->query($createPantrySql) === TRUE && $conn->query($createShopSql) === TRUE) {
            $messageRegister = "Your Account was created :D. Log in and pantry my friend!";
        } else {
            $messageRegister = "User created, but error creating tables :( " . $conn->error;
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Register - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="login.css">
</head>

<body class="login-page">

    <!-- Top Navigation -->
    <nav class="top-nav">
        <div class="nav-container">
            <div class="nav-left">
                <a href="index.php" class="nav-brand">
                    <img src="PantryPilotlogo2.png" style="height: 50px;">
                </a>
                <a href="index.php">Home</a>
                <a href="tracking.php">Pantry</a>
                <a href="grocery.php">Shopping List</a>
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

    <div class="login-hero">
        <div class="hero-overlay"></div>
    </div>

    <main class="main-content">
        <div class="auth-container">
            <div class="auth-box card-glass">
                <div class="box-header">
                    <h2><i class="fa-solid fa-right-to-bracket"></i> Welcome Back</h2>
                    <p>Enter your details to access your pantry.</p>
                </div>

                <form method="POST" class="modern-form">
                    <div class="input-group">
                        <label><i class="fa-solid fa-user"></i> Username</label>
                        <input type="text" name="username" required placeholder="Your username">
                    </div>
                    
                    <div class="input-group">
                        <label><i class="fa-solid fa-lock"></i> Password</label>
                        <input type="password" name="password" required placeholder="• • • • • • • •">
                    </div>

                    <button type="submit" class="btn-submit" name="login">
                        Log In <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>

                <?php if ($messageLogin): ?>
                    <div class="alert message-error">
                        <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($messageLogin) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="auth-box card-glass">
                <div class="box-header">
                    <h2><i class="fa-solid fa-user-plus"></i> Create Account</h2>
                    <p>Join Pantry Pilot and stop food waste today.</p>
                </div>

                <form method="POST" class="modern-form">
                    <div class="input-group">
                        <label><i class="fa-solid fa-user-tag"></i> Username</label>
                        <input type="text" name="r_username" required placeholder="Choose a username">
                    </div>

                    <div class="input-group">
                        <label><i class="fa-solid fa-id-card"></i> Full Name</label>
                        <input type="text" name="r_fullname" required placeholder="Your full name">
                    </div>

                    <div class="grid-2-col">
                        <div class="input-group">
                            <label><i class="fa-solid fa-envelope"></i> Email</label>
                            <input type="email" name="r_email" required placeholder="hello@example.com">
                        </div>
                        <div class="input-group">
                            <label><i class="fa-solid fa-phone"></i> Phone</label>
                            <input type="text" name="r_phone" required placeholder="(123) 456-7890">
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label><i class="fa-solid fa-lock"></i> Password</label>
                        <input type="password" name="r_password" required placeholder="Create a strong password">
                    </div>

                    <button type="submit" class="btn-submit btn-accent" name="register">
                        Register Account <i class="fa-solid fa-user-check"></i>
                    </button>
                </form>

                <?php if ($messageRegister): ?>
                    <div class="alert message-success">
                        <i class="fa-solid fa-check-circle"></i> <?= htmlspecialchars($messageRegister) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</body>
</html>
