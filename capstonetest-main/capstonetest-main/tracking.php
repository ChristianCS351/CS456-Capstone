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

// Adjusted values (initialize globally)
$adjusted_values = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['name'], $_POST['calories'], $_POST['fats'], $_POST['carbs'], $_POST['protien'], $_POST['ingredients'], $_POST['size'])) {
    $name = htmlspecialchars($_POST['name']);
    $calories = (int) $_POST['calories'];
    $fats = (int) $_POST['fats'];
    $carbs = (int) $_POST['carbs'];
    $protien = (int) $_POST['protien'];
    $ingredients = htmlspecialchars($_POST['ingredients']);
    $size = (int) $_POST['size'];

    try {
        $insert_sql = 'INSERT INTO recipes (name, calories, fats, carbs, protien, ingredients, size) VALUES (:name, :calories, :fats, :carbs, :protien, :ingredients, :size)';
        $stmt = $pdo->prepare($insert_sql);
        $stmt->execute([
            ':name' => $name,
            ':calories' => $calories,
            ':fats' => $fats,
            ':carbs' => $carbs,
            ':protien' => $protien,
            ':ingredients' => $ingredients,
            ':size' => $size
        ]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit; // Prevent further execution after redirect
    } catch (PDOException $e) {
        $error_message = "Error adding recipe: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = (int) $_POST['delete_id'];

    try {
        $delete_sql = 'DELETE FROM recipes WHERE id = :id';
        $stmt = $pdo->prepare($delete_sql);
        $stmt->execute([':id' => $delete_id]);
        header("Location: " . $_SERVER['PHP_SELF']);
        exit; // Prevent further execution after redirect
    } catch (PDOException $e) {
        $error_message = "Error removing recipe: " . $e->getMessage();
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_size'])) {
    $recipe_id = (int)$_POST['recipe'];
    $new_size = (int)$_POST['new_size'];

    // Fetch the selected recipe
    $fetch_recipe_sql = "SELECT * FROM recipes WHERE id = :id";
    $stmt = $pdo->prepare($fetch_recipe_sql);
    $stmt->execute(['id' => $recipe_id]);
    $selected_recipe = $stmt->fetch();

    if ($selected_recipe) {
        $size_ratio = $new_size / $selected_recipe['size'];
        $adjusted_calories = round($selected_recipe['calories'] * $size_ratio);
        $adjusted_fats = round($selected_recipe['fats'] * $size_ratio);
        $adjusted_carbs = round($selected_recipe['carbs'] * $size_ratio);
        $adjusted_protein = round($selected_recipe['protien'] * $size_ratio);

        // Store adjusted values for displaying
        $adjusted_values = [
            'name' => htmlspecialchars($selected_recipe['name']),
            'calories' => $adjusted_calories,
            'fats' => $adjusted_fats,
            'carbs' => $adjusted_carbs,
            'protein' => $adjusted_protein
        ];
    } else {
        $error_message = "Recipe not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracking Page</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .header {
    background-color: darkgreen;
    background-image: url('trackingtop.jpg');
    background-size: cover;
    background-position: center;
    background-color: rgba(255, 255, 255, 0.45); /* subtle overlay */
    background-blend-mode: overlay;
    color: lightgreen;
    padding: 20px;
    text-align: center;
    text-shadow: 
        -1px -1px 0 green,
        1px -1px 0 green,
        -1px 1px 0 green,
        1px 1px 0 green; /* creates a green border effect */
}

        .search-bar {
            margin: 20px auto;
            text-align: center;
        }
        .search-bar input {
            padding: 10px;
            width: 300px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .search-bar button {
            padding: 10px 15px;
            background-color: darkgreen;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .container {
            display: flex;
            justify-content: space-between;
            margin: 20px;
        }
        .table-container, .form-container {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            width: 48%;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        table th {
            background-color: darkgreen;
            color: white;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input, textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button.submit-btn {
            width: 100%;
            padding: 10px;
            background-color: darkgreen;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button.submit-btn:hover {
            background-color: green;
        }

        .return-button {
            position: absolute;
            top: 55px;
            left: 20px;
            transform: translateY(-50%);
            background-color: white;
            color: darkgreen;
            border: 2px solid darkgreen;
            border-radius: 8px;
            padding: 10px 15px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.2s ease, color 0.2s ease;
        }
        .return-button:hover {
            background-color: lightgreen;
            color: white;
        }

        .hover-cell {
            position: relative;
        }
        .hover-cell button {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: red;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            cursor: pointer;
        }
        .hover-cell:hover button {
            display: block;
        }
    </style>
</head>


<!-- created table display and entry box, and had gpt split it into sections and format it -->

<body>
    <div class="header">
        
        <button class="return-button" onclick="location.href='index.php';">Return to Home</button>
        <h1>Recipes</h1>
    </div>
    <style> background-color: rgba(93, 180, 98, 0.7);
    </style>
    <div class="search-bar">
        <form action="" method="get">
            <input type="text" name="search" placeholder="Search for a recipe..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="container">
        <!-- Table Section -->
        <div class="table-container">
            <h2>Recipes</h2>
            <table>
                <thead>
                    <tr>
                        
                        <th>Name</th>
                        <th>Calories</th>
                        <th>Fats</th>
                        <th>Carbs</th>
                        <th>Protein</th>
                        <th>Ingredients</th>
                        <th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recipes): ?>
                        <?php foreach ($recipes as $recipe): ?>
                            <tr>
                                
                                <td class="hover-cell">
                                    <?php echo htmlspecialchars($recipe['name']); ?>
                                    <form action="" method="post" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?php echo $recipe['id']; ?>">
                                        <button type="submit">Remove</button>
                                    </form>
                                </td>
                                
                                <td><?php echo htmlspecialchars($recipe['calories']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['fats']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['carbs']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['protien']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['ingredients']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['size']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No recipes found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container">
            <h2>Add a Recipe</h2>
            <form action="" method="post">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
                <label for="calories">Calories</label>
                <input type="number" id="calories" name="calories" required>
                <label for="fats">Fats</label>
                <input type="number" id="fats" name="fats" required>
                <label for="carbs">Carbs</label>
                <input type="number" id="carbs" name="carbs" required>
                <label for="protien">Protein</label>
                <input type="number" id="protien" name="protien" required>
                <label for="ingredients">Ingredients</label>
                <textarea id="ingredients" name="ingredients" required></textarea>
                <label for="size">Size (grams)</label>
                <input type="text" id="size" name="size" required>

                <button type="submit" class="submit-btn">Submit</button>
            </form>
        </div>
    </div>

        <div class="ratio-container" style="margin: 20px auto; text-align: center; background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); background-image: url('trackingheader.jpg');background-size: cover;
    background-position: center;
    background-color: rgba(255, 255, 255, 0.8);background-blend-mode: overlay;">
        <h2>Exact Recipe Macros</h2>
        <form action="" method="post">
            <label for="recipe">Select Recipe</label>
            <select id="recipe" name="recipe" style="padding: 10px; margin: 10px; width: 300px;">
                <?php foreach ($recipes as $recipe): ?>
                    <option value="<?php echo $recipe['id']; ?>"><?php echo htmlspecialchars($recipe['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="new_size">Enter Weight (grams)</label>
            <input type="number" id="new_size" name="new_size" style="padding: 10px; margin: 10px; width: 300px;" required>
            <button type="submit" name="adjust_size" style="padding: 10px 15px; background-color: darkgreen; color: white; border: none; border-radius: 4px; cursor: pointer;">Adjust</button>
        </form>

        <?php if ($adjusted_values): ?>
    <div style="margin-top: 20px; background-color: #ffffff; padding: 20px; border-radius: 10px; text-align: center; max-width: 500px; margin-left: auto; margin-right: auto; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);">
        <h3 style="color: darkgreen; margin-bottom: 20px;">Exact Weight Macros "<?php echo $adjusted_values['name']; ?>"</h3>
        <ul style="list-style-type: none; padding: 0; margin: 0; font-size: 18px;">
            <li style="margin: 10px 0;"><strong>Calories:</strong> <?php echo $adjusted_values['calories']; ?></li>
            <li style="margin: 10px 0;"><strong>Fats:</strong> <?php echo $adjusted_values['fats']; ?> g</li>
            <li style="margin: 10px 0;"><strong>Carbs:</strong> <?php echo $adjusted_values['carbs']; ?> g</li>
            <li style="margin: 10px 0;"><strong>Protein:</strong> <?php echo $adjusted_values['protein']; ?> g</li>
        </ul>
    </div>
<?php elseif ($error_message): ?>
    <p style="color: red; text-align: center; margin-top: 20px;"><?php echo $error_message; ?></p>
<?php endif; ?>

    </div>

</body>
</html>
