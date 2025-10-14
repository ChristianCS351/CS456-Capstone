<?php
$host = 'localhost'; 
$dbname = '351final'; 
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
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

$query = "SELECT * FROM recipes";
if ($search) {
    $query .= " WHERE name LIKE :search";
}
$stmt = $pdo->prepare($query);
if ($search) {
    $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
}
$stmt->execute();
$recipes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['name']) && isset($_POST['calories']) && isset($_POST['fats']) && isset($_POST['carbs']) && isset($_POST['protien']) && isset($_POST['ingredients']) && isset($_POST['size'])) {
        // Insert new recipe
        $name = htmlspecialchars($_POST['name']);
        $calories = (int) $_POST['calories'];
        $fats = (int) $_POST['fats'];
        $carbs = (int) $_POST['carbs'];
        $protien = (int) $_POST['protien'];
        $ingredients = htmlspecialchars($_POST['ingredients']);
        $size = htmlspecialchars($_POST['size']);

        $insert_sql = 'INSERT INTO recipes (name, calories, fats, carbs, protien, ingredients, size) VALUES (:name, :calories, :fats, :carbs, :protien, :ingredients, :size)';
        $stmt_insert = $pdo->prepare($insert_sql);
        $stmt_insert->execute([
            'name' => $name,
            'calories' => $calories,
            'fats' => $fats,
            'carbs' => $carbs,
            'protien' => $protien,
            'ingredients' => $ingredients,
            'size' => $size
        ]);
    } elseif (isset($_POST['delete_id'])) {
        // Delete a recipe
        $delete_id = (int) $_POST['delete_id'];

        $delete_sql = 'DELETE FROM recipes WHERE id = :id';
        $stmt_delete = $pdo->prepare($delete_sql);
        $stmt_delete->execute(['id' => $delete_id]);
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Nutrition Tracker</title>
    <link rel="stylesheet" href="styles.css">
    <style>
    header {
        background-image: url('header.jpg');
        background-size: 100%;
        background-position: center;
        background-color: rgba(255, 255, 255, 0.3); /* subtle overlay */
        background-blend-mode: overlay;
        padding:150px;
    }
    </style>
</head>
<body>
    <!-- llm polished this up, looks much better, also added mouse hovering animation -->
<header style="border: 2px solid white; border-radius: 8px; padding: 20px; text-align: center; background-color: #f4f4f4; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
    <nav>
    <h1 style="color: green; font-family: Arial, sans-serif; margin-bottom: 20px;">Bryson's Nutrition Tracker</h1>
        <style>
            button {
                background-color: darkgreen;
                color: white;
                border: none;
                border-radius: 12px;
                padding: 15px 20px;
                font-size: 16px;
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
                transition: transform 0.2s ease, box-shadow 0.2s ease; /* Smooth transition for hover effects */
            }
            button:hover {
                transform: scale(1.1); /* Increases size by 10% */
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3); /* Adds a stronger shadow on hover */
            }
        </style>
        <button onclick="window.location.href='tracking.php';">
            Track Macros
        </button>
        <button style="position: absolute; top: 20px; right: 20px; background-color: darkgreen; color: white; border: none; border-radius: 12px; padding: 10px 15px; font-size: 14px; cursor: pointer; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); transition: transform 0.2s ease, box-shadow 0.2s ease;" onclick="window.location.href='login.php';">
        Login
    </button>
    </nav>
</header>


    <main>

    <section class="dailys">
    <h2 style="text-align: center;">Daily Recipes</h2>
    <p style="text-align: center;">Today's 3 recipes</p>

    <div class="container" style="display: flex; justify-content: center; gap: 100px; text-align: center; margin-top: 20px;">

        <!-- First Recipe -->
        <div class="item" style="width: 190px;">
            <img src="pasta.jpg" alt="Image 1" style="width: 100%; border-radius: 8px;">
            <p>Four Cheese Pasta</p>
            <form action="" method="post">
                <input type="hidden" name="name" value="Four Cheese Pasta">
                <input type="hidden" name="calories" value="500">
                <input type="hidden" name="fats" value="20">
                <input type="hidden" name="carbs" value="50">
                <input type="hidden" name="protien" value="15">
                <input type="hidden" name="ingredients" value="Cheese, Pasta, Cream">
                <input type="hidden" name="size" value="550">
                <button type="submit" style="margin-top: 10px; padding: 5px 10px; background-color: darkgreen; color: white; border: none; border-radius: 5px; cursor: pointer;">Add Recipe</button>
            </form>
        </div>

        <!-- Second Recipe -->
        <div class="item" style="width: 190px;">
            <img src="sushi.jpg" alt="Image 2" style="width: 100%; border-radius: 8px;">
            <p>Tango Roll</p>
            <form action="" method="post">
                <input type="hidden" name="name" value="Tango Roll">
                <input type="hidden" name="calories" value="500">
                <input type="hidden" name="fats" value="20">
                <input type="hidden" name="carbs" value="50">
                <input type="hidden" name="protien" value="15">
                <input type="hidden" name="ingredients" value="Rice, Salmon, Avocado">
                <input type="hidden" name="size" value="360">
                <button type="submit" style="margin-top: 10px; padding: 5px 10px; background-color: darkgreen; color: white; border: none; border-radius: 5px; cursor: pointer;">Add Recipe</button>
            </form>
        </div>

        <!-- Third Recipe -->
        <div class="item" style="width: 190px;">
            <img src="glorp.jpg" alt="Image 3" style="width: 100%; border-radius: 8px;">
            <p>Zlorpian Stew</p>
            <form action="" method="post">
                <input type="hidden" name="name" value="Zlorpian Stew">
                <input type="hidden" name="calories" value="500">
                <input type="hidden" name="fats" value="20">
                <input type="hidden" name="carbs" value="50">
                <input type="hidden" name="protien" value="15">
                <input type="hidden" name="ingredients" value="Alien Herbs, Broth, Mystery Meat">
                <input type="hidden" name="size" value="700">
                <button type="submit" style="margin-top: 10px; padding: 5px 10px; background-color: darkgreen; color: white; border: none; border-radius: 5px; cursor: pointer;">Add Recipe</button>
            </form>
        </div>
    </div>
</section>

        
        <section class="about">
            <h2 style="text-align: center;">Why Use Our Tracker?</h2>
            <table>
                <tr>
                <td style="text-align: center">When you use Bryson's Nutrition Tracker, you are able to keep track of your macros all in one convienint place. And this can be even more accurate when paired with a basic food scale. </td></tr>
            </table>
        </section>

    
    </main>
    <footer>
        <nav><p></p>| 
            <a href="about.php">Help</a> | 
            
        </p></nav>
    </footer>
</body>
</html>
