/**
 * Video Teaser — Frontend Scripts
 *
 * Initializes Plyr players, manages teaser looping via timeupdate,
 * handles play/revert logic, and enforces one-active-at-a-time.
 *
 * @package Video_Teaser
 */
(function () {
    'use strict';

    var debug = typeof vtFrontend !== 'undefined' && vtFrontend.debug;
    var instances = {};
    var activeId = null;

    function log(msg) {
        if (debug && console && console.log) {
            console.log('[VideoTeaser] ' + msg);
        }
    }

    /**
     * Extract the first frame from a video using canvas.
     * Returns a blob URL via callback that can be used as a poster image.
     */
    function extractFirstFrame(video, onSuccess, onError) {
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        if (!ctx) {
            onError(new Error('Canvas not supported'));
            return;
        }

        var onSeeked = function() {
            canvas.width = video.videoWidth || video.clientWidth || 640;
            canvas.height = video.videoHeight || video.clientHeight || 360;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            canvas.toBlob(function(blob) {
                if (blob) {
                    var url = URL.createObjectURL(blob);
                    onSuccess(url);
                } else {
                    onError(new Error('Failed to create blob'));
                }
            }, 'image/jpeg', 0.8);
        };

        video.addEventListener('seeked', onSeeked, { once: true });
        video.currentTime = 0.1; // Seek to get first frame
    }

    function safePlay(player) {
        var promise = player.play();
        if (promise && typeof promise.catch === 'function') {
            promise.catch(function (e) {
                log('Play failed: ' + e.message);
            });
        }
    }

    /* -------------------------------------------
       Initialize all containers
       ------------------------------------------- */
    function initAll() {
        var containers = document.querySelectorAll('.vt-container');
        log('Found ' + containers.length + ' container(s)');

        for (var i = 0; i < containers.length; i++) {
            initContainer(containers[i]);
        }
    }

    /* -------------------------------------------
       Initialize a single container
       ------------------------------------------- */
    function initContainer(container) {
        var id = container.id;
        if (instances[id]) return;

        var sourceType = container.getAttribute('data-vt-source');
        var teaserEnabled = container.getAttribute('data-vt-teaser') === '1';
        var startTime = parseInt(container.getAttribute('data-vt-start'), 10) || 0;
        var endTime = parseInt(container.getAttribute('data-vt-end'), 10) || 10;

        if (teaserEnabled && startTime >= endTime) {
            log(id + ': startTime >= endTime, disabling teaser loop');
            startTime = 0;
            endTime = 10;
        }

        var ratioAttr = container.getAttribute('data-vt-ratio') || '16:9';

        var playerEl = container.querySelector('.vt-player');
        if (!playerEl) {
            log('No .vt-player found in ' + id);
            return;
        }

        // Get the player wrapper for loading state
        var playerWrap = container.querySelector('.vt-player-wrap');

        // Add loading state for videos without poster images (self-hosted only)
        // Use canvas-based first frame extraction for reliable mobile support
        if (playerWrap && playerEl.tagName === 'VIDEO' && !playerEl.poster) {
            playerWrap.classList.add('vt-loading');

            // Safety timeout: remove spinner after 3 seconds regardless
            var loadingTimeout = setTimeout(function() {
                playerWrap.classList.remove('vt-loading');
                log(id + ': Loading timeout - removing spinner');
            }, 3000);

            var removeLoading = function() {
                clearTimeout(loadingTimeout);
                playerWrap.classList.remove('vt-loading');
            };

            // Force metadata load for mobile browsers
            playerEl.load();

            // Extract first frame when metadata is ready
            var handleMetadata = function() {
                extractFirstFrame(playerEl, function(blobUrl) {
                    playerEl.poster = blobUrl;
                    removeLoading();
                    log(id + ': First frame extracted and set as poster');
                }, function(err) {
                    // Fallback: just remove loading state
                    removeLoading();
                    log(id + ': First frame extraction failed: ' + err.message);
                });
            };

            if (playerEl.readyState >= 1) {
                handleMetadata();
            } else {
                playerEl.addEventListener('loadedmetadata', handleMetadata, { once: true });
            }
        }

        // Detect aspect ratio before Plyr initialization for self-hosted videos.
        // This handles the case where metadata loads before Plyr is created.
        var detectedRatio = null;
        if (ratioAttr === 'auto' && playerEl.tagName === 'VIDEO') {
            if (playerEl.videoWidth && playerEl.videoHeight) {
                detectedRatio = playerEl.videoWidth + ':' + playerEl.videoHeight;
                log(id + ': Pre-detected ratio ' + detectedRatio);
            }
        }

        // Determine the ratio to use for Plyr initialization.
        var plyrRatio;
        if (detectedRatio) {
            plyrRatio = detectedRatio;
        } else if (ratioAttr !== 'auto') {
            plyrRatio = ratioAttr;
        } else {
            plyrRatio = '16:9'; // Fallback for auto when metadata not yet loaded
        }

        var plyrOptions = {
            controls: [
                'play-large',    // Large play button in center
                'play',          // Play/pause in control bar
                'progress',      // Progress/seek bar
                'current-time',  // Current time display
                'mute',          // Mute toggle
                'volume',        // Volume slider
                'captions',      // Captions toggle
                'settings',      // Settings menu
                'pip',           // Picture-in-Picture
                'airplay',       // AirPlay for Apple devices
                'fullscreen',    // Fullscreen toggle
            ],
            settings: ['captions', 'quality', 'speed'],
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
            ratio: plyrRatio,
            storage: { enabled: false },
            keyboard: { focused: true, global: false },
            tooltips: { controls: true, seek: true },
            clickToPlay: true,
            hideControls: true,
            autopause: false,
            resetOnEnd: false,
            muted: teaserEnabled,
            autoplay: teaserEnabled,
            loop: { active: false },
            youtube: {
                noCookie: true,
                rel: 0,
                modestbranding: 1,
                playsinline: 1,
            },
            vimeo: {
                byline: false,
                portrait: false,
                title: false,
                transparent: false,
            },
        };

        var player;
        try {
            player = new Plyr(playerEl, plyrOptions);
        } catch (e) {
            log('Plyr init failed for ' + id + ': ' + e.message);
            return;
        }

        // Listen for metadata in case it loads after Plyr init (only if not already detected).
        if (ratioAttr === 'auto' && playerEl.tagName === 'VIDEO' && !detectedRatio) {
            playerEl.addEventListener('loadedmetadata', function () {
                var w = playerEl.videoWidth;
                var h = playerEl.videoHeight;
                if (w && h) {
                    player.ratio = w + ':' + h;
                    log(id + ': Post-detected ratio ' + w + ':' + h);
                }
            });
        }

        var instance = {
            player: player,
            container: container,
            sourceType: sourceType,
            teaserEnabled: teaserEnabled,
            startTime: startTime,
            endTime: endTime,
            isTeaser: teaserEnabled,
        };

        instances[id] = instance;

        // Inject play-full overlay button for teaser-enabled videos.
        if (teaserEnabled) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'vt-play-full';
            btn.setAttribute('aria-label', 'Play full video');
            var hasSpritePlay = !!document.getElementById('plyr-play');
            var svgContent = hasSpritePlay
                ? '<svg aria-hidden="true" focusable="false"><use xlink:href="#plyr-play"></use></svg>'
                : '<svg aria-hidden="true" focusable="false" viewBox="0 0 18 18"><path d="M15.562 8.1L3.87.573c-.344-.215-.79.042-.79.437v15.04c0 .395.446.652.79.437L15.563 8.9c.344-.215.344-.753 0-.8z" fill="currentColor"/></svg>';
            btn.innerHTML = svgContent + '<span class="plyr__sr-only">Play</span>';
            container.appendChild(btn);
            container.classList.add('vt-teaser-active');

            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                playFull(id);
            });
        }

        // When an unmuted video starts, revert the previous unmuted video.
        // Muted teasers are invisible to this logic.
        player.on('play', function () {
            if (instance.isTeaser) return;

            if (activeId && activeId !== id && instances[activeId]) {
                revertToTeaser(activeId);
            }
            activeId = id;
        });

        // Teaser loop via timeupdate.
        if (teaserEnabled) {
            var isSeeking = false;
            player.on('seeked', function () { isSeeking = false; });

            player.on('ready', function () {
                log(id + ' player ready');
                seekToStart(instance);
                player.toggleControls(false);
            });

            player.on('timeupdate', function () {
                if (!instance.isTeaser || isSeeking) return;

                var current = player.currentTime;
                var duration = player.duration;
                var effectiveEnd = (duration > 0 && endTime > duration) ? duration - 0.5 : endTime;

                // If past end, loop back to start.
                if (current >= effectiveEnd) {
                    isSeeking = true;
                    player.currentTime = instance.startTime;
                }
            });

            // When player can play, seek and start.
            player.on('canplay', function () {
                if (instance.isTeaser) {
                    var duration = player.duration;
                    if (duration > 0 && instance.startTime >= duration) {
                        instance.startTime = 0;
                    }
                    seekToStart(instance);
                }
            });

            player.on('ended', function () {
                if (instance.isTeaser) {
                    player.currentTime = instance.startTime;
                    safePlay(player);
                }
            });
        }

        log('Initialized ' + id + ' (' + sourceType + ')');
    }

    /* -------------------------------------------
       Seek to teaser start
       ------------------------------------------- */
    function seekToStart(instance) {
        var player = instance.player;
        if (player.currentTime < instance.startTime || player.currentTime >= instance.endTime) {
            player.currentTime = instance.startTime;
        }
        player.muted = true;
        safePlay(player);
    }

    /* -------------------------------------------
       Switch to full video mode
       ------------------------------------------- */
    function playFull(id) {
        log('Play full: ' + id);

        // Revert any currently active video.
        if (activeId && activeId !== id && instances[activeId]) {
            revertToTeaser(activeId);
        }

        var instance = instances[id];
        if (!instance) return;

        instance.isTeaser = false;
        instance.container.classList.remove('vt-teaser-active');
        activeId = id;

        var player = instance.player;
        player.muted = false;
        try {
            player.restart();
        } catch (e) {
            player.currentTime = 0;
            safePlay(player);
        }

        log('Now playing full: ' + id);
    }

    /* -------------------------------------------
       Revert to teaser mode
       ------------------------------------------- */
    function revertToTeaser(id) {
        log('Reverting to teaser: ' + id);

        var instance = instances[id];
        if (!instance) return;

        if (instance.teaserEnabled) {
            instance.isTeaser = true;
            instance.player.muted = true;
            instance.player.currentTime = instance.startTime;
            safePlay(instance.player);
            instance.player.toggleControls(false);
            instance.container.classList.add('vt-teaser-active');
        } else {
            instance.player.stop();
        }

        if (activeId === id) {
            activeId = null;
        }
    }

    /* -------------------------------------------
       DOM Ready
       ------------------------------------------- */
    function onReady() {
        if (typeof Plyr === 'undefined') {
            log('Plyr not loaded');
            return;
        }
        initAll();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', onReady);
    } else {
        onReady();
    }

    /* -------------------------------------------
       Public API
       ------------------------------------------- */
    window.VideoTeaser = {
        play: playFull,
        revert: revertToTeaser,
        instances: instances,
    };
})();
