<?php
require_once 'includes/config.php';

try {
    // Check published news
    $stmt = $pdo->prepare("SELECT id, title, status, published_at FROM news WHERE status = 'published' AND published_at <= NOW() ORDER BY published_at DESC LIMIT 3");
    $stmt->execute();
    $news_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Published news items found: " . count($news_items) . "\n\n";

    foreach ($news_items as $news) {
        echo "ID: {$news['id']}\n";
        echo "Title: {$news['title']}\n";
        echo "Status: {$news['status']}\n";
        echo "Published At: {$news['published_at']}\n";
        echo "---\n";
    }

    // Check all news
    $stmt = $pdo->query("SELECT id, title, status, published_at FROM news ORDER BY created_at DESC");
    $all_news = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "\nAll news items:\n";
    foreach ($all_news as $news) {
        echo "ID: {$news['id']} - {$news['title']} ({$news['status']}) - " . ($news['published_at'] ? $news['published_at'] : 'NULL') . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>