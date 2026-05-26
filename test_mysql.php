<?php
try {
    $pdo = new PDO('mysql:host=localhost;port=3306', 'root', '');
    echo "Connected successfully to MySQL\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
