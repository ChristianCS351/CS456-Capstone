<?php 
session_start();

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

// Prevent access if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

    try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$pantry_table = isset($_SESSION['pantry_table']) ? $_SESSION['pantry_table'] : 'foods';
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT username, email, full_name, phone FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$userInfo = $stmt->fetch();

if (!$userInfo) {
    die("User not found.");
}


$pantry_table = isset($_SESSION['pantry_table']) ? $_SESSION['pantry_table'] : 'foods';
$shop_table = isset($_SESSION['shop_table']) ? $_SESSION['shop_table'] : 'foods';



// DB connection
// $conn = new mysqli("localhost", "root", "mysql", "pantry");

// $stmt = $conn->prepare("SELECT username, email, full_name, phone FROM users WHERE user_id = ?");
// $stmt->bind_param("i", $user_id);
// $stmt->execute();
// $result = $stmt->get_result();
// $userInfo = $result->fetch_assoc();
// $stmt->close();


// Used this helpful source to help me get ideas on how to delete tables. https://www.mssqltips.com/sqlservertip/6769/sql-server-drop-table-if-exists/
// I also used this incredible source as well. https://www.w3schools.com/php/php_mysql_delete.asp
// Delete Account from Database Section

if (isset($_POST['delete'])) {
     try {
        $pdo->beginTransaction();

        $deleteUser = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
        $deleteUser->execute([$user_id]);

        //This drops the Table Stuff for both pantry and shopping list

        $pantryTable = $_SESSION['pantry_table'] ?? null;
        if ($pantryTable) {
            $pdo->exec("DROP TABLE IF EXISTS `$pantryTable`");
        }

        $shopTable = $_SESSION['shop_table'] ?? null;
        if ($shopTable) {
            $pdo->exec("DROP TABLE IF EXISTS `$shopTable`");
        }

        if($pdo->inTransaction()) {
        $pdo->commit();
        }

        $_SESSION = [];
        session_destroy();

        header("Location: login.php?deleted=1");
        exit;

    } catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
        die("Error deleting account: " . $e->getMessage());
    }
}

//    $conn = mysqli_connect($host, $user, $pass, $dbname);
// // Check connection
//    if (!$conn) {
//         die("Connection failed: " . mysqli_connect_error());
// }

// // SQL to delete a record
//     $sql_delete = "DELETE FROM users WHERE phone = ($userInfo['phone'])";
//     $sql_delete2 = "DROP * FROM TABLE `christianc3_pantry`";

//     if ($conn->query($sql_delete) === TRUE && $conn->query($sql_delete2) === TRUE) {
//             $messageDelete = "Your Account was Deleted :(";
//         } else {
//             $messageDelete = "User deleted, but error creating tables :( " . $conn->error;
//         }


// mysqli_close($conn);
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete - Pantry Pilot</title>
    <link rel="icon" type="image/x-icon" href="favicon-32x32.png">

    <!-- Modern Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="delete.css">
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
            <h1><i class="fa-solid fa-user-circle"></i> Delete Profile</h1>
            <p>Welcome back, <?php echo htmlspecialchars($userInfo['full_name']); ?>.</p>
        </div>
    </header>

    <main class="main-content">
        <div class="content-row">

            <!-- account info details -->
            <div class="container card-modern">
                <div class="card-header">
                    <div class="card-header">
                    <h2><i class="fa-solid fa-address-card" style="color: #d00000" ></i> Delete</h2>
                </div>

                    <!-- This asks the User if they want to delete their account. -->
                    <form method="POST" id="delete_form">
                        <button type="submit" name="delete" class="btn-action btn-danger d-block mt-4 text-center" class="logout-btn" onclick= "return deleteOut()">
                        <i class="fa-solid fa-bomb"></i>Delete Account
                        </button>
                    </form>
                </div>
            </div>

            <!-- pantry items that are about to expire -->
            <div class="pantry-box card-modern">
                <div class="card-header">
                    <div class="card-header">
                    <h2><i class="fa-notdog fa-solid fa-xmark" style="color:red"></i> IMPORTANT CONSIDERATIONS</h2>
                </div>
                
                <div class="card-body">
                    <p class="text-muted mb-3"><i class="fa-solid fa-info-circle"></i> Note you will lose all things:<br><br> 
                    1. Lose all pantry storage and grocery lists permanently.<br> 2. Lose your grocery list stored history.<br> 3. Lose you account information from the website.</p>

                </div>
            </div>
            </div>
        </div>
    </main>

    <!-- Optional Scripts -->
    <script src="app.js"></script>
    <script src="delete.js"></script>
</body>
</html>
