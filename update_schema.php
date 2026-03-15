<?php
$pdo = new PDO('mysql:host=localhost;dbname=pantry', 'root', 'mysql');
$pdo->exec('ALTER TABLE foods MODIFY COLUMN id BIGINT AUTO_INCREMENT');
$pdo->exec('ALTER TABLE foods MODIFY COLUMN barcode BIGINT');
$pdo->exec('ALTER TABLE shop_list MODIFY COLUMN id BIGINT AUTO_INCREMENT');
$pdo->exec('ALTER TABLE shop_list MODIFY COLUMN barcode BIGINT');
echo "Schema updated successfully\n";
