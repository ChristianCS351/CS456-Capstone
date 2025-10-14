<?php

$host = 'localhost';
$user = 'root'; 
$pass = 'mysql'; 
$dbname = '351final';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-image: url('loginabackground.jpg');
            background-size: cover;
            background-position: center;
        }
        .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 100vh;
            padding: 0 50px;
        }
        .login, .register {
            background-color: rgba(255, 255, 255, 0.8);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 40%;
        }
        h2 {
            margin-top: 0;
            text-align: center;
            color: darkgreen;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #333;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: darkgreen;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        button:hover {
            background-color: green;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Login Section -->
        <div class="login">
            <h2>Login</h2>
            <form action="" method="post">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
                
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
                
                <button type="submit" name="login">Login</button>
            </form>
            <?php
            if (isset($_POST['login'])) {
                $username = $_POST['username'];
                $password = $_POST['password'];

                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->bindValue(':username', $username, PDO::PARAM_STR);
                $stmt->execute();
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password'])) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['username'] = $username;
                    header('Location: index.php');
                    exit;
                } else {
                    echo "<p style='color: red; text-align: center;'>Invalid username or password.</p>";
                }
            }
            ?>
        </div>

        <!-- Register Section -->
        <div class="register">
            <h2>Register</h2>
            <form action="" method="post">
                <label for="new_username">Username</label>
                <input type="text" id="new_username" name="new_username" required>

                <label for="new_password">Password</label>
                <input type="password" id="new_password" name="new_password" required>

                <label for="confirm_password">Verify Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
                
                <button type="submit" name="register">Register</button>
            </form>
            <?php
            if (isset($_POST['register'])) {
                $new_username = $_POST['new_username'];
                $new_password = $_POST['new_password'];
                $confirm_password = $_POST['confirm_password'];

                if ($new_password !== $confirm_password) {
                    echo "<p style='color: red; text-align: center;'>Passwords do not match.</p>";
                } else {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
                    $stmt->bindValue(':username', $new_username, PDO::PARAM_STR);
                    $stmt->bindValue(':password', $hashed_password, PDO::PARAM_STR);

                    if ($stmt->execute()) {
                        echo "<p style='color: green; text-align: center;'>Account Created!</p>";
                    }
                }
            }
            ?>
        </div>
    </div>
</body>
</html>
