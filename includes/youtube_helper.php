<?php
/**
 * YouTube Helper Functions - NO cURL (file_get_contents)
 * File: includes/youtube_helper.php
 */

require_once __DIR__ . '/../config/youtube_config.php';

/**
 * Fetch video information from YouTube API (using file_get_contents)
 */
function fetchYouTubeVideoInfo($videoId) {
    if (!isYouTubeAPIConfigured()) {
        return [
            'success' => false,
            'error' => 'YouTube API key not configured'
        ];
    }
    
    $apiKey = YOUTUBE_API_KEY;
    $endpoint = YOUTUBE_API_ENDPOINT;
    
    $url = $endpoint . '?' . http_build_query([
        'part' => 'snippet,contentDetails,statistics',
        'id' => $videoId,
        'key' => $apiKey
    ]);
    
    // Use file_get_contents instead of cURL
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => "User-Agent: Mozilla/5.0\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);
    
    $response = @file_get_contents($url, false, $context);
    
    if ($response === false) {
        return [
            'success' => false,
            'error' => 'Failed to fetch video info. Please check your internet connection.'
        ];
    }
    
    // Check HTTP response code
    if (isset($http_response_header)) {
        $status_line = $http_response_header[0];
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $httpCode = isset($match[1]) ? (int)$match[1] : 0;
        
        if ($httpCode !== 200) {
            return [
                'success' => false,
                'error' => 'HTTP Error: ' . $httpCode . ' - Video may be private or deleted'
            ];
        }
    }
    
    $data = json_decode($response, true);
    
    // Check if response is valid JSON
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'success' => false,
            'error' => 'Invalid API response. Please check your API key configuration.'
        ];
    }
    
    // Check for API errors
    if (isset($data['error'])) {
        $errorMsg = $data['error']['message'] ?? 'Unknown API error';
        $errorCode = $data['error']['code'] ?? 0;
        
        if ($errorCode == 403) {
            return [
                'success' => false,
                'error' => 'API Key error: ' . $errorMsg . '. Please check your YouTube API key restrictions.'
            ];
        }
        
        return [
            'success' => false,
            'error' => 'YouTube API Error (' . $errorCode . '): ' . $errorMsg
        ];
    }
    
    if (empty($data['items'])) {
        return [
            'success' => false,
            'error' => 'Video not found or private'
        ];
    }
    
    $video = $data['items'][0];
    $snippet = $video['snippet'] ?? [];
    $contentDetails = $video['contentDetails'] ?? [];
    $statistics = $video['statistics'] ?? [];
    
    $duration = $contentDetails['duration'] ?? 'PT0S';
    $durationSeconds = parseYouTubeDuration($duration);
    
    return [
        'success' => true,
        'video_id' => $videoId,
        'title' => $snippet['title'] ?? 'Untitled',
        'description' => $snippet['description'] ?? '',
        'thumbnail' => $snippet['thumbnails']['maxresdefault']['url'] ?? 
                       $snippet['thumbnails']['high']['url'] ?? 
                       getYouTubeThumbnail($videoId),
        'duration' => $durationSeconds,
        'duration_formatted' => formatVideoDuration($durationSeconds),
        'channel_name' => $snippet['channelTitle'] ?? '',
        'published_at' => $snippet['publishedAt'] ?? '',
        'view_count' => (int)($statistics['viewCount'] ?? 0),
        'like_count' => (int)($statistics['likeCount'] ?? 0),
        'comment_count' => (int)($statistics['commentCount'] ?? 0),
    ];
}

/**
 * Cache YouTube video info
 */
function setYouTubeCache($videoId, $data) {
    try {
        $db = getDB();
        
        $expiresAt = date('Y-m-d H:i:s', time() + YOUTUBE_CACHE_DURATION);
        
        $stmt = $db->prepare("
            INSERT INTO youtube_cache (video_id, data, expires_at) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE 
            data = VALUES(data), 
            cached_at = CURRENT_TIMESTAMP,
            expires_at = VALUES(expires_at)
        ");
        
        $stmt->execute([$videoId, json_encode($data), $expiresAt]);
        
    } catch (Exception $e) {
        error_log("YouTube cache error: " . $e->getMessage());
    }
}

/**
 * Get cached YouTube video info
 */
function getYouTubeCache($videoId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT data FROM youtube_cache 
            WHERE video_id = ? AND expires_at > NOW()
        ");
        
        $stmt->execute([$videoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            return json_decode($row['data'], true);
        }
        
    } catch (Exception $e) {
        error_log("YouTube cache retrieve error: " . $e->getMessage());
    }
    
    return null;
}

/**
 * Get user progress for a specific materi
 */
function getUserMateriProgress($userId, $materiId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT * FROM progress_materi 
            WHERE user_id = ? AND materi_id = ?
        ");
        
        $stmt->execute([$userId, $materiId]);
        $progress = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$progress) {
            $stmt = $db->prepare("
                INSERT INTO progress_materi 
                (user_id, materi_id, status, persentase_selesai, tanggal_mulai) 
                VALUES (?, ?, 'belum_mulai', 0, NOW())
            ");
            $stmt->execute([$userId, $materiId]);
            
            return [
                'user_id' => $userId,
                'materi_id' => $materiId,
                'status' => 'belum_mulai',
                'persentase_selesai' => 0,
                'waktu_tonton' => 0,
                'last_position' => 0,
                'completed' => 0
            ];
        }
        
        return $progress;
        
    } catch (Exception $e) {
        error_log("Error getting progress: " . $e->getMessage());
        return null;
    }
}

/**
 * Update user progress for watching video
 */
function updateUserProgress($userId, $materiId, $position, $duration, $watchDuration) {
    try {
        $db = getDB();
        
        $percentage = $duration > 0 ? round(($position / $duration) * 100, 1) : 0;
        $percentage = min(100, $percentage);
        
        $completed = $percentage >= 90 ? 1 : 0;
        $status = $completed ? 'selesai' : 'sedang_dipelajari';
        
        $stmt = $db->prepare("
            SELECT progress_id, waktu_tonton FROM progress_materi 
            WHERE user_id = ? AND materi_id = ?
        ");
        $stmt->execute([$userId, $materiId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $totalWatchTime = ($existing['waktu_tonton'] ?? 0) + $watchDuration;
            
            $updateSql = "
                UPDATE progress_materi SET 
                    status = ?,
                    persentase_selesai = ?,
                    waktu_tonton = ?,
                    last_position = ?,
                    completed = ?,
                    tanggal_selesai = " . ($completed ? "NOW()" : "NULL") . ",
                    updated_at = NOW()
                WHERE user_id = ? AND materi_id = ?
            ";
            
            $stmt = $db->prepare($updateSql);
            $stmt->execute([
                $status,
                $percentage,
                $totalWatchTime,
                $position,
                $completed,
                $userId,
                $materiId
            ]);
        } else {
            $insertSql = "
                INSERT INTO progress_materi 
                (user_id, materi_id, status, persentase_selesai, waktu_tonton, last_position, completed, tanggal_mulai, tanggal_selesai) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), " . ($completed ? "NOW()" : "NULL") . ")
            ";
            
            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                $userId,
                $materiId,
                $status,
                $percentage,
                $watchDuration,
                $position,
                $completed
            ]);
        }
        
        $stmt = $db->prepare("
            INSERT INTO watch_history 
            (user_id, materi_id, watch_duration, video_position) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $materiId, $watchDuration, $position]);
        
        return [
            'success' => true,
            'percentage' => $percentage,
            'status' => $status,
            'completed' => $completed
        ];
        
    } catch (Exception $e) {
        error_log("Error updating progress: " . $e->getMessage());
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Update or create progress for materi (backward compatibility)
 */
function updateMateriProgress($userId, $materiId, $videoPosition, $videoDuration) {
    return updateUserProgress($userId, $materiId, $videoPosition, $videoDuration, $videoPosition);
}

/**
 * Log watch history
 */
function logWatchHistory($userId, $materiId, $watchDuration, $videoPosition) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            INSERT INTO watch_history (user_id, materi_id, watch_duration, video_position)
            VALUES (?, ?, ?, ?)
        ");
        
        $stmt->execute([$userId, $materiId, $watchDuration, $videoPosition]);
        
        return true;
        
    } catch (Exception $e) {
        error_log("Log watch history error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get total watch time for user
 */
function getUserTotalWatchTime($userId) {
    try {
        $db = getDB();
        
        $stmt = $db->prepare("
            SELECT SUM(waktu_tonton) as total_seconds
            FROM progress_materi
            WHERE user_id = ?
        ");
        
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int)($result['total_seconds'] ?? 0);
        
    } catch (Exception $e) {
        error_log("Get total watch time error: " . $e->getMessage());
        return 0;
    }
}
?>