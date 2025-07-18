<?php
require_once 'config.php';

$plaintext_password = 'admin123'; // <--- CHANGE THIS TO YOUR DESIRED PASSWORD
$hashed_password = password_hash($plaintext_password, PASSWORD_DEFAULT);

echo "Plaintext Password: " . $plaintext_password . "<br>";
echo "Hashed Password: " . $hashed_password . "<br>";

try {
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->execute([$hashed_password, 1]);
    echo "Database updated successfully for admin user.";
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage();
}
?>