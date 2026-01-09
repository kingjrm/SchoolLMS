<?php
// Test database connection and news query
try {
    require_once 'includes/config.php';

    // Test basic connection
    $stmt = $pdo->query("SELECT 1");
    echo "Database connection: OK\n";

    // Test news table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'news'");
    $table_exists = $stmt->fetch();

    if ($table_exists) {
        echo "News table exists: OK\n";

        // Test news query
        $stmt = $pdo->prepare("SELECT id, title FROM news WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3");
        $stmt->execute();
        $news = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "News query executed: OK\n";
        echo "News items found: " . count($news) . "\n";

        foreach ($news as $item) {
            echo "- " . $item['title'] . "\n";
        }
    } else {
        echo "News table does not exist\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>