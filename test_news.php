<?php
require_once 'includes/config.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM news');
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo 'News table exists with ' . $result['count'] . ' records';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>