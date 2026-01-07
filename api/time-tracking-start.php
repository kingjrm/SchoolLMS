<?php
/**
 * API endpoint to start a time tracking session
 * Called when a student enters a page
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
    // End any open session first
    TimeTracking::endSession($pdo, $user['id']);
    
    // Start new session
    $course_id = $_POST['course_id'] ?? null;
    $assignment_id = $_POST['assignment_id'] ?? null;
    $page_type = $_POST['page_type'] ?? 'general';
    
    $result = TimeTracking::startSession($pdo, $user['id'], $course_id, $assignment_id, $page_type);
    
    echo json_encode(['success' => $result]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
