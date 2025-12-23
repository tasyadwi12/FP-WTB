<?php
/**
 * Watch Materi Page with YouTube Player & Notes
 * File: pages/siswa/watch-materi.php
 */

define('APP_NAME', 'Web Tracking Belajar');
require_once '../../config/config.php';
require_once '../../config/youtube_config.php';
require_once '../../includes/youtube_helper.php';

requireRole(ROLE_SISWA);

$page_title = 'Belajar Materi';
$current_page = 'materi-list';

$user_id = getUserId();
$materi_id = (int)($_GET['id'] ?? 0);

if ($materi_id <= 0) {
    header('Location: materi-list.php');
    exit;
}

// Get materi details
try {
    $db = getDB();
    
    $stmt = $db->prepare("
        SELECT m.*, k.nama_kategori
        FROM materi m
        LEFT JOIN kategori_materi k ON m.kategori_id = k.kategori_id
        WHERE m.materi_id = ? AND m.is_active = 1
    ");
    
    $stmt->execute([$materi_id]);
    $materi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$materi) {
        setFlash(ERROR, 'Materi tidak ditemukan');
        header('Location: materi-list.php');
        exit;
    }
    
} catch (Exception $e) {
    setFlash(ERROR, 'Error: ' . $e->getMessage());
    header('Location: materi-list.php');
    exit;
}

// Get user progress (including notes)
$progress = getUserMateriProgress($user_id, $materi_id);
$lastPosition = $progress['last_position'] ?? 0;
$userNotes = $progress['catatan'] ?? '';

include '../../includes/header.php';
include '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="container-fluid">
        
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="materi-list.php">Materi</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($materi['judul']); ?></li>
            </ol>
        </nav>
        
        <div class="row">
            
            <!-- Main Content -->
            <div class="col-lg-8">
                
                <!-- Video Player -->
                <?php if (!empty($materi['youtube_video_id'])): ?>
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-0">
                            <div class="ratio ratio-16x9" id="player-container">
                                <div id="youtube-player"></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Info -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">Progress Belajar</h6>
                                <span class="badge bg-primary" id="progress-percentage">
                                    <?php echo $progress['persentase_selesai'] ?? 0; ?>%
                                </span>
                            </div>
                            <div class="progress" style="height: 10px;">
                                <div class="progress-bar bg-success" 
                                     id="progress-bar"
                                     style="width: <?php echo $progress['persentase_selesai'] ?? 0; ?>%">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-clock me-1"></i>
                                    <span id="current-time">0:00</span> / 
                                    <span id="total-time"><?php echo formatVideoDuration($materi['video_duration'] ?? 0); ?></span>
                                </small>
                                <small class="text-muted" id="status-text">
                                    <?php 
                                    $status = $progress['status'] ?? 'belum_mulai';
                                    echo $status === 'selesai' ? 'Selesai' : 
                                         ($status === 'sedang_dipelajari' ? 'Sedang Belajar' : 'Belum Mulai'); 
                                    ?>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Materi ini belum memiliki video pembelajaran.
                    </div>
                <?php endif; ?>
                
                <!-- Materi Info -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h3 class="mb-3"><?php echo htmlspecialchars($materi['judul']); ?></h3>
                        
                        <div class="mb-3">
                            <span class="badge <?php 
                                echo $materi['tingkat_kesulitan'] === 'pemula' ? 'bg-success' : 
                                    ($materi['tingkat_kesulitan'] === 'menengah' ? 'bg-warning' : 'bg-danger'); 
                            ?>">
                                <?php echo ucfirst($materi['tingkat_kesulitan']); ?>
                            </span>
                            
                            <?php if (!empty($materi['nama_kategori'])): ?>
                                <span class="badge bg-light text-dark ms-2">
                                    <i class="fas fa-tag me-1"></i>
                                    <?php echo htmlspecialchars($materi['nama_kategori']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <h5>Deskripsi</h5>
                        <p class="text-muted">
                            <?php echo nl2br(htmlspecialchars($materi['deskripsi'])); ?>
                        </p>
                    </div>
                </div>
                
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                
                <!-- Learning Stats -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h6 class="card-title mb-3">Statistik Belajar</h6>
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Waktu Tonton</small>
                                <strong><?php echo formatDuration($progress['waktu_tonton'] ?? 0); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Status</small>
                                <strong class="text-<?php 
                                    $status = $progress['status'] ?? 'belum_mulai';
                                    echo $status === 'selesai' ? 'success' : 
                                         ($status === 'sedang_dipelajari' ? 'info' : 'secondary'); 
                                ?>">
                                    <?php 
                                    echo $status === 'selesai' ? 'Selesai' : 
                                         ($status === 'sedang_dipelajari' ? 'Sedang Belajar' : 'Belum Mulai'); 
                                    ?>
                                </strong>
                            </div>
                            
                            <?php if (!empty($progress['tanggal_mulai'])): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Mulai</small>
                                    <strong><?php echo date('d/m/Y', strtotime($progress['tanggal_mulai'])); ?></strong>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($progress['tanggal_selesai'])): ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Selesai</small>
                                    <strong><?php echo date('d/m/Y', strtotime($progress['tanggal_selesai'])); ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fas fa-sticky-note me-2"></i>Catatan Belajar
                        </h6>
                        <textarea class="form-control" 
                                  rows="6" 
                                  placeholder="Tulis catatan belajar kamu di sini..." 
                                  id="notes"><?php echo htmlspecialchars($userNotes); ?></textarea>
                        <button class="btn btn-sm btn-primary mt-2 w-100" onclick="saveNotes()">
                            <i class="fas fa-save me-2"></i>Simpan Catatan
                        </button>
                        <div id="notes-status" class="mt-2 text-center small"></div>
                    </div>
                </div>
                
            </div>
            
        </div>
        
    </div>
</div>

<!-- YouTube Player API -->
<script src="https://www.youtube.com/iframe_api"></script>

<script>
// Materi & User data
const MATERI_ID = <?php echo $materi_id; ?>;
const VIDEO_ID = '<?php echo $materi['youtube_video_id'] ?? ''; ?>';
const VIDEO_DURATION = <?php echo $materi['video_duration'] ?? 0; ?>;
const LAST_POSITION = <?php echo $lastPosition; ?>;

let player;
let progressInterval;
let startWatchTime;
let lastSavedPosition = LAST_POSITION;

// Initialize YouTube Player
function onYouTubeIframeAPIReady() {
    player = new YT.Player('youtube-player', {
        height: '100%',
        width: '100%',
        videoId: VIDEO_ID,
        playerVars: {
            'autoplay': 0,
            'controls': 1,
            'rel': 0,
            'modestbranding': 1
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
}

// Player ready
function onPlayerReady(event) {
    console.log('Player ready');
    
    // Seek to last position if exists
    if (LAST_POSITION > 0) {
        player.seekTo(LAST_POSITION, true);
    }
    
    startWatchTime = Date.now();
}

// Player state change
function onPlayerStateChange(event) {
    if (event.data == YT.PlayerState.PLAYING) {
        console.log('Video playing');
        startProgressTracking();
    } else if (event.data == YT.PlayerState.PAUSED) {
        console.log('Video paused');
        stopProgressTracking();
        saveProgress();
    } else if (event.data == YT.PlayerState.ENDED) {
        console.log('Video ended');
        stopProgressTracking();
        saveProgress(true);
    }
}

// Start tracking progress
function startProgressTracking() {
    if (progressInterval) clearInterval(progressInterval);
    
    progressInterval = setInterval(() => {
        updateUI();
        
        // Auto-save every 30 seconds
        const currentTime = player.getCurrentTime();
        if (Math.abs(currentTime - lastSavedPosition) >= 30) {
            saveProgress();
        }
    }, 1000);
}

// Stop tracking
function stopProgressTracking() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
    }
}

// Update UI
function updateUI() {
    if (!player || !player.getCurrentTime) return;
    
    const currentTime = Math.floor(player.getCurrentTime());
    const duration = player.getDuration() || VIDEO_DURATION;
    const percentage = duration > 0 ? Math.min(100, Math.round((currentTime / duration) * 100)) : 0;
    
    // Update time display
    document.getElementById('current-time').textContent = formatTime(currentTime);
    document.getElementById('total-time').textContent = formatTime(duration);
    
    // Update progress bar
    document.getElementById('progress-bar').style.width = percentage + '%';
    document.getElementById('progress-percentage').textContent = percentage + '%';
    
    // Update status
    let status = 'Sedang Belajar';
    if (percentage >= 90) {
        status = 'Selesai';
    }
    document.getElementById('status-text').textContent = status;
}

// Save progress to server
async function saveProgress(isCompleted = false) {
    if (!player || !player.getCurrentTime) return;
    
    const currentTime = Math.floor(player.getCurrentTime());
    const duration = player.getDuration() || VIDEO_DURATION;
    const watchDuration = Math.floor((Date.now() - startWatchTime) / 1000);
    
    try {
        const response = await fetch('../../api/youtube/update_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                materi_id: MATERI_ID,
                position: currentTime,
                duration: duration,
                watch_duration: watchDuration
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            console.log('Progress saved:', data.data);
            lastSavedPosition = currentTime;
            startWatchTime = Date.now(); // Reset watch time
            
            // Show completion message
            if (data.data.completed && isCompleted) {
                showCompletionMessage();
            }
        } else {
            console.error('Save failed:', data.error);
        }
    } catch (error) {
        console.error('Save error:', error);
    }
}

// Save notes
async function saveNotes() {
    const notes = document.getElementById('notes').value;
    const statusDiv = document.getElementById('notes-status');
    
    // Show loading
    statusDiv.innerHTML = '<span class="text-muted"><i class="fas fa-spinner fa-spin me-1"></i>Menyimpan...</span>';
    
    try {
        const response = await fetch('../../api/youtube/save_notes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                materi_id: MATERI_ID,
                catatan: notes
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            statusDiv.innerHTML = '<span class="text-success"><i class="fas fa-check-circle me-1"></i>Catatan tersimpan!</span>';
            setTimeout(() => {
                statusDiv.innerHTML = '';
            }, 3000);
        } else {
            statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>' + (data.error || 'Gagal menyimpan') + '</span>';
        }
    } catch (error) {
        console.error('Save notes error:', error);
        statusDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle me-1"></i>Terjadi kesalahan!</span>';
    }
}

// Show completion message
function showCompletionMessage() {
    const msg = document.createElement('div');
    msg.className = 'alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3';
    msg.style.zIndex = '9999';
    msg.innerHTML = `
        <strong><i class="fas fa-check-circle me-2"></i>Selamat!</strong>
        Kamu telah menyelesaikan materi ini.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(msg);
    
    setTimeout(() => msg.remove(), 5000);
}

// Format time helper
function formatTime(seconds) {
    const h = Math.floor(seconds / 3600);
    const m = Math.floor((seconds % 3600) / 60);
    const s = Math.floor(seconds % 60);
    
    if (h > 0) {
        return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    } else {
        return `${m}:${s.toString().padStart(2, '0')}`;
    }
}

// Save progress before leaving page
window.addEventListener('beforeunload', (e) => {
    if (player && player.getPlayerState() === YT.PlayerState.PLAYING) {
        saveProgress();
    }
});

// Periodic auto-save (every 2 minutes)
setInterval(() => {
    if (player && player.getPlayerState() === YT.PlayerState.PLAYING) {
        saveProgress();
    }
}, 120000); // 2 minutes
</script>

<?php include '../../includes/footer.php'; ?>