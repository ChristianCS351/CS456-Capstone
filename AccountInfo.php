<?php 
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_unset();    
    session_destroy();   
    header("Location: login.php");
    exit;
}

// Prevent access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Info - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="faviconPP.ico.jpg">

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="account.css">
</head>

<body class="account-page">

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

    <!-- Mini Hero Section -->
    <header class="mini-hero">
        <div class="mini-hero-bg"></div>
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1><i class="fa-solid fa-user-circle"></i> Account Profile</h1>
            <p>Welcome back, <?php echo htmlspecialchars($userInfo['full_name']); ?>.</p>
        </div>
    </header>

    <main class="main-content">
        <div class="content-row">

            <!-- account info details -->
            <div class="container card-modern">
                <div class="card-header">
                    <h2><i class="fa-solid fa-address-card"></i> Personal Information</h2>
                </div>
                
                <div class="card-body">
                    <div class="info-list">
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-user-tag"></i> Username:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userInfo['username']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-id-card"></i> Full Name:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userInfo['full_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-envelope"></i> Email:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userInfo['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label"><i class="fa-solid fa-phone"></i> Phone:</span>
                            <span class="info-value"><?php echo htmlspecialchars($userInfo['phone']); ?></span>
                        </div>
                    </div>

                    <!-- Note: For demo purposes, we direct logout to login.php. Normally this calls logout.php -->
                     <form method="POST" id="clear_form">
                        <button type="button" href="login.php" class="btn-action btn-danger d-block mt-4 text-center" class="logout-btn" onclick=handleOut()>
                        <i class="fa-solid fa-person-walking-arrow-right"></i>Secure Logout 
                     </form>
                </div>
            </div>

            <!-- pantry items that are about to expire -->
            <div class="pantry-box card-modern">
                <div class="card-header">
                    <h2><i class="fa-solid fa-bell" style="color:#f59e0b"></i> Action Required</h2>
                </div>
                
                <div class="card-body">
                    <p class="text-muted mb-3"><i class="fa-solid fa-info-circle"></i> The following items in your pantry are expiring in the next 7 days:</p>

                    <div class="alerts-list">
                        <?php if (empty($expiringItems)) : ?>
                            <div class="alert-success-box text-center">
                                <i class="fa-solid fa-check-circle fa-3x" style="color: #4caf50; display:block; margin-bottom: 10px;"></i>
                                <p style="font-weight: 600; color: #1b5e20;">All Good!</p>
                                <p style="font-size: 0.9rem; color: #1b5e20;">No items are expiring soon.</p>
                            </div>
                        <?php else : ?>
                            <ul class="expiring-ul">
                                <?php foreach ($expiringItems as $item): ?>
                                    <li>
                                        <div class="item-name"><strong><?php echo htmlspecialchars($item['name']); ?></strong></div>
                                        <div class="item-exp"><span class="badge badge-warning"><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($item['expiration_date']); ?></span></div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- Optional Scripts -->
    <script src="app.js"></script>
    <script src="account.js"></script>
</body>
</html>
