<?php
$pdo = new PDO('mysql:host=localhost;dbname=pantry', 'root', 'mysql');
$stmt = $pdo->query('DESCRIBE foods');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
