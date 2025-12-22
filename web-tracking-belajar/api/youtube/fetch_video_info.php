<?php
/**
 * API: Fetch YouTube Video Information
 * File: api/youtube/fetch_video_info.php
 * 
 * Usage: POST with { "url": "youtube_url" }
 */

header('Content-Type: application/json');

require_once '../../config/config.php';
require_once '../../config/youtube_config.php';
require_once '../../includes/youtube_helper.php';

// Check if user is logged in (optional - comment out for public access)
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized'
    ]);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$url = $input['url'] ?? $_POST['url'] ?? '';

if (empty($url)) {
    echo json_encode([
        'success' => false,
        'error' => 'URL is required'
    ]);
    exit;
}

// Extract video ID
$videoId = extractYouTubeID($url);

if (!$videoId) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid YouTube URL'
    ]);
    exit;
}

// Fetch video info
$result = fetchYouTubeVideoInfo($videoId);

echo json_encode($result);
?>