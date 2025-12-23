/**
 * YouTube Player Handler
 * File: assets/js/youtube-player.js
 * 
 * Optional standalone version jika mau dipisah dari watch-materi.php
 * Tapi di watch-materi.php sudah ada inline, jadi file ini OPTIONAL
 */

class YouTubePlayerTracker {
    constructor(config) {
        this.materiId = config.materiId;
        this.videoId = config.videoId;
        this.videoDuration = config.videoDuration;
        this.lastPosition = config.lastPosition || 0;
        this.apiEndpoint = config.apiEndpoint || '/api/youtube/update_progress.php';
        
        this.player = null;
        this.progressInterval = null;
        this.startWatchTime = null;
        this.lastSavedPosition = this.lastPosition;
        this.autoSaveInterval = config.autoSaveInterval || 30; // seconds
        
        this.init();
    }
    
    init() {
        // Load YouTube IFrame API
        if (!window.YT) {
            const tag = document.createElement('script');
            tag.src = 'https://www.youtube.com/iframe_api';
            const firstScriptTag = document.getElementsByTagName('script')[0];
            firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
        }
        
        // Wait for API to load
        window.onYouTubeIframeAPIReady = () => {
            this.createPlayer();
        };
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    createPlayer() {
        this.player = new YT.Player('youtube-player', {
            height: '100%',
            width: '100%',
            videoId: this.videoId,
            playerVars: {
                'autoplay': 0,
                'controls': 1,
                'rel': 0,
                'modestbranding': 1,
                'playsinline': 1
            },
            events: {
                'onReady': (e) => this.onPlayerReady(e),
                'onStateChange': (e) => this.onPlayerStateChange(e)
            }
        });
    }
    
    onPlayerReady(event) {
        console.log('YouTube Player ready');
        
        // Seek to last position
        if (this.lastPosition > 0) {
            this.player.seekTo(this.lastPosition, true);
        }
        
        this.startWatchTime = Date.now();
        this.emit('ready', { player: this.player });
    }
    
    onPlayerStateChange(event) {
        const states = {
            '-1': 'unstarted',
            '0': 'ended',
            '1': 'playing',
            '2': 'paused',
            '3': 'buffering',
            '5': 'cued'
        };
        
        const state = states[event.data] || 'unknown';
        console.log('Player state:', state);
        
        switch(event.data) {
            case YT.PlayerState.PLAYING:
                this.startTracking();
                this.emit('play');
                break;
                
            case YT.PlayerState.PAUSED:
                this.stopTracking();
                this.saveProgress();
                this.emit('pause');
                break;
                
            case YT.PlayerState.ENDED:
                this.stopTracking();
                this.saveProgress(true);
                this.emit('ended');
                break;
        }
    }
    
    startTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
        }
        
        this.progressInterval = setInterval(() => {
            this.updateUI();
            
            // Auto-save check
            const currentTime = Math.floor(this.player.getCurrentTime());
            if (Math.abs(currentTime - this.lastSavedPosition) >= this.autoSaveInterval) {
                this.saveProgress();
            }
        }, 1000);
    }
    
    stopTracking() {
        if (this.progressInterval) {
            clearInterval(this.progressInterval);
            this.progressInterval = null;
        }
    }
    
    updateUI() {
        if (!this.player || typeof this.player.getCurrentTime !== 'function') {
            return;
        }
        
        const currentTime = Math.floor(this.player.getCurrentTime());
        const duration = this.player.getDuration() || this.videoDuration;
        const percentage = duration > 0 ? Math.min(100, Math.round((currentTime / duration) * 100)) : 0;
        
        this.emit('progress', {
            currentTime,
            duration,
            percentage,
            formatted: {
                current: this.formatTime(currentTime),
                duration: this.formatTime(duration)
            }
        });
    }
    
    async saveProgress(isCompleted = false) {
        if (!this.player || typeof this.player.getCurrentTime !== 'function') {
            return;
        }
        
        const currentTime = Math.floor(this.player.getCurrentTime());
        const duration = this.player.getDuration() || this.videoDuration;
        const watchDuration = Math.floor((Date.now() - this.startWatchTime) / 1000);
        
        try {
            const response = await fetch(this.apiEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    materi_id: this.materiId,
                    position: currentTime,
                    duration: duration,
                    watch_duration: watchDuration
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('Progress saved:', data.data);
                this.lastSavedPosition = currentTime;
                this.startWatchTime = Date.now();
                
                this.emit('progressSaved', data.data);
                
                // Check completion
                if (data.data.completed && isCompleted) {
                    this.emit('completed', data.data);
                }
            } else {
                console.error('Save failed:', data.error);
                this.emit('error', { type: 'save', error: data.error });
            }
        } catch (error) {
            console.error('Save error:', error);
            this.emit('error', { type: 'save', error: error.message });
        }
    }
    
    formatTime(seconds) {
        const h = Math.floor(seconds / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = Math.floor(seconds % 60);
        
        if (h > 0) {
            return `${h}:${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        } else {
            return `${m}:${s.toString().padStart(2, '0')}`;
        }
    }
    
    setupEventListeners() {
        // Save before leaving page
        window.addEventListener('beforeunload', () => {
            if (this.player && this.player.getPlayerState() === YT.PlayerState.PLAYING) {
                this.saveProgress();
            }
        });
        
        // Periodic auto-save (every 2 minutes)
        setInterval(() => {
            if (this.player && this.player.getPlayerState() === YT.PlayerState.PLAYING) {
                this.saveProgress();
            }
        }, 120000);
    }
    
    // Event emitter
    emit(event, data) {
        const customEvent = new CustomEvent('youtubeTracker:' + event, { detail: data });
        document.dispatchEvent(customEvent);
    }
    
    // Public methods
    play() {
        if (this.player) {
            this.player.playVideo();
        }
    }
    
    pause() {
        if (this.player) {
            this.player.pauseVideo();
        }
    }
    
    seekTo(seconds) {
        if (this.player) {
            this.player.seekTo(seconds, true);
        }
    }
    
    getCurrentTime() {
        return this.player ? Math.floor(this.player.getCurrentTime()) : 0;
    }
    
    getDuration() {
        return this.player ? this.player.getDuration() : this.videoDuration;
    }
    
    getPercentage() {
        const current = this.getCurrentTime();
        const duration = this.getDuration();
        return duration > 0 ? Math.round((current / duration) * 100) : 0;
    }
    
    destroy() {
        this.stopTracking();
        if (this.player) {
            this.player.destroy();
        }
    }
}

// Auto-init if data attributes present
document.addEventListener('DOMContentLoaded', () => {
    const playerContainer = document.getElementById('youtube-player');
    
    if (playerContainer && playerContainer.dataset.autoInit === 'true') {
        const tracker = new YouTubePlayerTracker({
            materiId: parseInt(playerContainer.dataset.materiId),
            videoId: playerContainer.dataset.videoId,
            videoDuration: parseInt(playerContainer.dataset.duration),
            lastPosition: parseInt(playerContainer.dataset.lastPosition || 0),
            apiEndpoint: playerContainer.dataset.apiEndpoint
        });
        
        // Listen to events and update UI
        document.addEventListener('youtubeTracker:progress', (e) => {
            const { percentage, formatted } = e.detail;
            
            // Update progress bar
            const progressBar = document.getElementById('progress-bar');
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
            
            // Update percentage text
            const progressText = document.getElementById('progress-percentage');
            if (progressText) {
                progressText.textContent = percentage + '%';
            }
            
            // Update time display
            const currentTimeEl = document.getElementById('current-time');
            if (currentTimeEl) {
                currentTimeEl.textContent = formatted.current;
            }
            
            const totalTimeEl = document.getElementById('total-time');
            if (totalTimeEl) {
                totalTimeEl.textContent = formatted.duration;
            }
        });
        
        // Handle completion
        document.addEventListener('youtubeTracker:completed', (e) => {
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
        });
        
        // Store globally
        window.youtubeTracker = tracker;
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = YouTubePlayerTracker;
}