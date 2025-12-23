<?php
/**
 * YouTube API Configuration - FIXED (No Duplicate Function)
 * File: config/youtube_config.php
 */

// YouTube API Settings
define('YOUTUBE_API_KEY', 'AIzaSyCzMEgpDn5z1aS2KN_rI14wVllWGsNsr4E');
define('YOUTUBE_API_ENDPOINT', 'https://www.googleapis.com/youtube/v3/videos');

// YouTube Video URL Patterns
define('YOUTUBE_URL_PATTERNS', [
    '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
    '/youtu\.be\/([a-zA-Z0-9_-]+)/',
    '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
    '/youtube\.com\/v\/([a-zA-Z0-9_-]+)/'
]);

// Cache settings (optional - untuk mengurangi API calls)
define('YOUTUBE_CACHE_ENABLED', true);
define('YOUTUBE_CACHE_DURATION', 3600 * 24 * 7); // 7 hari

/**
 * Extract YouTube Video ID from URL
 */
function extractYouTubeID($url) {
    if (empty($url)) return null;
    
    foreach (YOUTUBE_URL_PATTERNS as $pattern) {
        if (preg_match($pattern, $url, $matches)) {
            return $matches[1];
        }
    }
    
    // If URL is already just the video ID
    if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $url)) {
        return $url;
    }
    
    return null;
}

/**
 * Format duration from ISO 8601 to seconds
 */
function parseYouTubeDuration($duration) {
    // Duration format: PT#H#M#S
    preg_match('/PT(\d+H)?(\d+M)?(\d+S)?/', $duration, $matches);
    
    $hours = isset($matches[1]) ? (int)str_replace('H', '', $matches[1]) : 0;
    $minutes = isset($matches[2]) ? (int)str_replace('M', '', $matches[2]) : 0;
    $seconds = isset($matches[3]) ? (int)str_replace('S', '', $matches[3]) : 0;
    
    return ($hours * 3600) + ($minutes * 60) + $seconds;
}

/**
 * Get YouTube video thumbnail URL
 */
function getYouTubeThumbnail($videoId, $quality = 'maxresdefault') {
    // Qualities: default, mqdefault, hqdefault, sddefault, maxresdefault
    return "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
}

/**
 * Validate YouTube API Key
 */
function isYouTubeAPIConfigured() {
    return YOUTUBE_API_KEY !== 'YOUR_YOUTUBE_API_KEY_HERE' && !empty(YOUTUBE_API_KEY);
}
?>