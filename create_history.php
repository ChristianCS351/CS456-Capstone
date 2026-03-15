<?php
$pdo = new PDO('mysql:host=localhost;dbname=pantry', 'root', 'mysql');
$pdo->exec('CREATE TABLE IF NOT EXISTS food_history (barcode BIGINT PRIMARY KEY, name VARCHAR(99) NOT NULL, dairy VARCHAR(99))');
echo "History table created\n";
