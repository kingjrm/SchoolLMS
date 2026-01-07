<?php
/**
 * Course Join Code Migration Script
 * Generates unique join codes for all active courses that don't have one
 * 
 * Usage: Run this script once from the admin panel or via command line
 * php database/generate-join-codes.php
 */

require_once '../includes/config.php';
require_once '../includes/CodeGenerator.php';

try {
    // Get all courses without join codes
    $stmt = $pdo->prepare("
        SELECT id, code, title 
        FROM courses 
        WHERE join_code IS NULL AND status = 'active'
        ORDER BY id
    ");
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($courses)) {
        echo "✓ All active courses already have join codes.\n";
        exit;
    }
    
    echo "Generating join codes for " . count($courses) . " courses...\n";
    echo str_repeat("-", 60) . "\n";
    
    $updated = 0;
    foreach ($courses as $course) {
        $joinCode = CodeGenerator::generateUniqueJoinCode($pdo);
        
        $updateStmt = $pdo->prepare("UPDATE courses SET join_code = ? WHERE id = ?");
        $updateStmt->execute([$joinCode, $course['id']]);
        
        echo "✓ " . str_pad($course['code'], 15) . " | " . $joinCode . "\n";
        $updated++;
    }
    
    echo str_repeat("-", 60) . "\n";
    echo "✓ Successfully generated join codes for $updated courses.\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
