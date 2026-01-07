<?php
require_once 'includes/config.php';

// Update admin and teacher accounts to be verified and active
$query = "UPDATE users SET is_verified=1, status='active' WHERE role IN ('admin', 'teacher')";
$stmt = $pdo->prepare($query);
$stmt->execute();

$affected = $stmt->rowCount();

if ($affected > 0) {
    echo "✅ Success! Updated $affected admin/teacher accounts.<br>";
    echo "They can now login without email verification.<br>";
    echo "<a href='login.php'>Go to Login</a>";
} else {
    echo "❌ No admin/teacher accounts found or already verified.";
}
?>
