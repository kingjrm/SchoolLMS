<?php
/**
 * API endpoint to end a time tracking session
 * Called when a student leaves a page
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/Auth.php';
require_once __DIR__ . '/../includes/TimeTracking.php';

header('Content-Type: application/json');

Auth::requireLogin();
$user = Auth::getCurrentUser();

if (!$user || $user['role'] !== 'student') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    $result = TimeTracking::endSession($pdo, $user['id']);
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
