<?php
// Database connection removed for local testing
// $host = 'localhost';
// $user = 'root'; 
// $pass = 'mysql'; 
// $dbname = '351final';

// try {
//     $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
//     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     die("Connection failed: " . $e->getMessage());
// }

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'];
    $problem = $_POST['problem'];

    // Skip database insertion and simulate a successful submission
    $message = "Your message has been sent (simulation only, database disabled).";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit a Question</title>
    
    <link rel="stylesheet" href="about.css">
</head>
<body>
    <div class="container">
        <button class="back-button" onclick="window.location.href='index.php';">Back to Home</button>
        <h2>Submit Your Question / Issue</h2>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>
        <form action="" method="post">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required>

            <label for="problem">Problem</label>
            <textarea id="problem" name="problem" required></textarea>

            <button type="submit">Send</button>
        </form>
    </div>
</body>
</html>
