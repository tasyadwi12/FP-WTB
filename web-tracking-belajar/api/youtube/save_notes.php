<?php
/**
 * API untuk Menyimpan Catatan Belajar
 * File: api/youtube/save_notes.php
 */

header('Content-Type: application/json');
require_once '../../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit;
}

$materi_id = (int)($input['materi_id'] ?? 0);
$catatan = trim($input['catatan'] ?? '');
$user_id = getUserId();

// Validate
if ($materi_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid materi_id']);
    exit;
}

try {
    $db = getDB();
    
    // Check if progress exists
    $stmt = $db->prepare("
        SELECT progress_id 
        FROM progress_materi 
        WHERE user_id = ? AND materi_id = ?
    ");
    $stmt->execute([$user_id, $materi_id]);
    $progress = $stmt->fetch();
    
    if ($progress) {
        // Update existing progress with notes
        $stmt = $db->prepare("
            UPDATE progress_materi 
            SET catatan = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE progress_id = ?
        ");
        $stmt->execute([$catatan, $progress['progress_id']]);
    } else {
        // Create new progress with notes
        $stmt = $db->prepare("
            INSERT INTO progress_materi 
            (user_id, materi_id, catatan, status, tanggal_mulai) 
            VALUES (?, ?, ?, 'sedang_dipelajari', CURDATE())
        ");
        $stmt->execute([$user_id, $materi_id, $catatan]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Catatan berhasil disimpan',
        'data' => [
            'catatan' => $catatan,
            'saved_at' => date('Y-m-d H:i:s')
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>