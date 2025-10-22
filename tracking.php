<?php
// Database disabled for local testing
// $host = 'localhost';
// $dbname = '351final';
// $user = 'root';
// $pass = 'mysql';
// $charset = 'utf8mb4';

// $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
// $options = [
//     PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     PDO::ATTR_EMULATE_PREPARES   => false,
// ];

// try {
//     $pdo = new PDO($dsn, $user, $pass, $options);
// } catch (PDOException $e) {
//     throw new PDOException($e->getMessage(), (int)$e->getCode());
// }

$search = '';
if (isset($_GET['search'])) {
    $search = $_GET['search'];
}

// Simulated recipe data for testing
$recipes = [
    ['id' => 1, 'name' => 'Four Cheese Pasta', 'calories' => 500, 'fats' => 20, 'carbs' => 50, 'protien' => 15, 'ingredients' => 'Cheese, Pasta, Cream', 'size' => 550],
    ['id' => 2, 'name' => 'Tango Roll', 'calories' => 360, 'fats' => 12, 'carbs' => 45, 'protien' => 18, 'ingredients' => 'Rice, Salmon, Avocado', 'size' => 360],
    ['id' => 3, 'name' => 'Zlorpian Stew', 'calories' => 700, 'fats' => 25, 'carbs' => 60, 'protien' => 22, 'ingredients' => 'Alien Herbs, Broth, Mystery Meat', 'size' => 700]
];

// Filter search
if ($search) {
    $recipes = array_filter($recipes, function($r) use ($search) {
        return stripos($r['name'], $search) !== false;
    });
}

// Simulated post responses
$adjusted_values = null;
$error_message = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adjust_size'])) {
    $recipe_id = (int)$_POST['recipe'];
    $new_size = (int)$_POST['new_size'];

    // Find the selected recipe
    $selected_recipe = null;
    foreach ($recipes as $r) {
        if ($r['id'] === $recipe_id) {
            $selected_recipe = $r;
            break;
        }
    }

    if ($selected_recipe) {
        $size_ratio = $new_size / $selected_recipe['size'];
        $adjusted_values = [
            'name' => $selected_recipe['name'],
            'calories' => round($selected_recipe['calories'] * $size_ratio),
            'fats' => round($selected_recipe['fats'] * $size_ratio),
            'carbs' => round($selected_recipe['carbs'] * $size_ratio),
            'protein' => round($selected_recipe['protien'] * $size_ratio)
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
        body { margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f4; }
        .header {
            background-color: darkgreen;
            background-image: url('trackingtop.jpg');
            background-size: cover;
            background-position: center;
            background-color: rgba(255, 255, 255, 0.45);
            background-blend-mode: overlay;
            color: lightgreen;
            padding: 20px;
            text-align: center;
            text-shadow: -1px -1px 0 green, 1px -1px 0 green, -1px 1px 0 green, 1px 1px 0 green;
        }
        .search-bar { margin:20px auto; text-align:center; }
        .search-bar input { padding:10px; width:300px; border:1px solid #ccc; border-radius:4px; }
        .search-bar button { padding:10px 15px; background-color:darkgreen; color:white; border:none; border-radius:4px; cursor:pointer; }
        .container { display:flex; justify-content:space-between; margin:20px; }
        .table-container, .form-container {
            background-color:white; border-radius:8px; padding:20px;
            box-shadow:0 4px 8px rgba(0,0,0,0.1); width:48%;
        }
        table { width:100%; border-collapse:collapse; }
        table th, table td { border:1px solid #ddd; padding:8px; text-align:center; }
        table th { background-color:darkgreen; color:white; }
        button.submit-btn {
            width:100%; padding:10px;
            background-color:darkgreen; color:white;
            border:none; border-radius:4px; cursor:pointer;
        }
        button.submit-btn:hover { background-color:green; }
        .return-button {
            position:absolute; top:55px; left:20px;
            background-color:white; color:darkgreen;
            border:2px solid darkgreen; border-radius:8px;
            padding:10px 15px; font-size:14px; cursor:pointer;
        }
        .return-button:hover { background-color:lightgreen; color:white; }
    </style>
</head>
<body>
    <div class="header">
        <button class="return-button" onclick="location.href='index.php';">Return to Home</button>
        <h1>Recipes</h1>
    </div>

    <div class="search-bar">
        <form action="" method="get">
            <input type="text" name="search" placeholder="Search for a recipe..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="container">
        <div class="table-container">
            <h2>Recipes</h2>
            <table>
                <thead>
                    <tr>
                        <th>Name</th><th>Calories</th><th>Fats</th><th>Carbs</th><th>Protein</th><th>Ingredients</th><th>Size</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recipes): ?>
                        <?php foreach ($recipes as $recipe): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($recipe['name']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['calories']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['fats']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['carbs']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['protien']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['ingredients']); ?></td>
                                <td><?php echo htmlspecialchars($recipe['size']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">No recipes found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="form-container">
            <h2>Add a Recipe (Simulation)</h2>
            <form method="post">
                <p style="color:gray;">Database disabled — form kept for appearance only.</p>
                <input type="text" placeholder="Name" required>
                <input type="number" placeholder="Calories" required>
                <input type="number" placeholder="Fats" required>
                <input type="number" placeholder="Carbs" required>
                <input type="number" placeholder="Protein" required>
                <textarea placeholder="Ingredients"></textarea>
                <input type="text" placeholder="Size (grams)">
                <button type="button" class="submit-btn">Submit (Offline)</button>
            </form>
        </div>
    </div>

    <div class="ratio-container" style="margin:20px auto; text-align:center; background-color:white; padding:20px; border-radius:8px; box-shadow:0 4px 8px rgba(0,0,0,0.1); max-width:600px;">
        <h2>Exact Recipe Macros</h2>
        <form action="" method="post">
            <label for="recipe">Select Recipe</label>
            <select id="recipe" name="recipe" style="padding:10px; margin:10px; width:300px;">
                <?php foreach ($recipes as $recipe): ?>
                    <option value="<?php echo $recipe['id']; ?>"><?php echo htmlspecialchars($recipe['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="new_size">Enter Weight (grams)</label>
            <input type="number" id="new_size" name="new_size" style="padding:10px; margin:10px; width:300px;" required>
            <button type="submit" name="adjust_size" style="padding:10px 15px; background-color:darkgreen; color:white; border:none; border-radius:4px;">Adjust</button>
        </form>

        <?php if ($adjusted_values): ?>
            <div style="margin-top:20px; background-color:#ffffff; padding:20px; border-radius:10px; text-align:center; box-shadow:0 4px 8px rgba(0,0,0,0.2);">
                <h3 style="color:darkgreen;">Exact Weight Macros "<?php echo htmlspecialchars($adjusted_values['name']); ?>"</h3>
                <ul style="list-style:none; padding:0; margin:0; font-size:18px;">
                    <li><strong>Calories:</strong> <?php echo $adjusted_values['calories']; ?></li>
                    <li><strong>Fats:</strong> <?php echo $adjusted_values['fats']; ?> g</li>
                    <li><strong>Carbs:</strong> <?php echo $adjusted_values['carbs']; ?> g</li>
                    <li><strong>Protein:</strong> <?php echo $adjusted_values['protein']; ?> g</li>
                </ul>
            </div>
        <?php elseif ($error_message): ?>
            <p style="color:red;"><?php echo $error_message; ?></p>
        <?php endif; ?>
    </div>
</body>
</html>
