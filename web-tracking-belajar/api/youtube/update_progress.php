<?php
/**
 * API: Update Watch Progress
 * File: api/youtube/update_progress.php
 * 
 * Usage: POST with { "materi_id": 1, "position": 120, "duration": 600 }
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../includes/youtube_helper.php';

// Must be logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);

$materiId = (int)($input['materi_id'] ?? 0);
$position = (int)($input['position'] ?? 0);
$duration = (int)($input['duration'] ?? 0);
$watchDuration = (int)($input['watch_duration'] ?? 0); // How long in this session

if ($materiId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid materi_id'
    ]);
    exit;
}

$userId = getUserId();

// Update progress
$progressId = updateMateriProgress($userId, $materiId, $position, $duration);

if (!$progressId) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update progress'
    ]);
    exit;
}

// Log watch history (optional)
if ($watchDuration > 0) {
    logWatchHistory($userId, $materiId, $watchDuration, $position);
}

// Get updated progress
$progress = getUserMateriProgress($userId, $materiId);

echo json_encode([
    'success' => true,
    'message' => 'Progress updated',
    'data' => [
        'progress_id' => $progressId,
        'percentage' => $progress['persentase_selesai'] ?? 0,
        'status' => $progress['status'] ?? 'belum_mulai',
        'completed' => (bool)($progress['completed'] ?? false),
        'last_position' => $progress['last_position'] ?? 0
    ]
]);
?>