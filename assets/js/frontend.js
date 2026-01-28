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

        var playerEl = container.querySelector('.vt-player');
        if (!playerEl) {
            log('No .vt-player found in ' + id);
            return;
        }

        var plyrOptions = {
            controls: ['play-large', 'play', 'progress', 'current-time', 'mute', 'volume', 'settings', 'fullscreen'],
            settings: ['speed'],
            speed: { selected: 1, options: [0.5, 0.75, 1, 1.25, 1.5, 2] },
            ratio: '16:9',
            storage: { enabled: false },
            keyboard: { focused: true, global: false },
            tooltips: { controls: true, seek: true },
            clickToPlay: true,
            hideControls: true,
            autopause: true,
            resetOnEnd: false,
            muted: teaserEnabled,
            autoplay: teaserEnabled,
            loop: { active: false },
            youtube: {
                noCookie: true,
                rel: 0,
                showinfo: 0,
                modestbranding: 1,
            },
            vimeo: {
                byline: false,
                portrait: false,
                title: false,
                transparent: false,
            },
        };

        var player = new Plyr(playerEl, plyrOptions);

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

        // Use Plyr's native play event for multi-video management.
        player.on('play', function () {
            if (!instance.isTeaser) {
                if (activeId && activeId !== id && instances[activeId]) {
                    revertToTeaser(activeId);
                }
                // Pause any auto-playing teasers.
                for (var otherId in instances) {
                    if (otherId !== id && instances[otherId].isTeaser) {
                        instances[otherId].player.pause();
                    }
                }
                activeId = id;
            }
        });

        // Teaser loop via timeupdate.
        if (teaserEnabled) {
            player.on('ready', function () {
                log(id + ' player ready');
                seekToStart(instance);
                player.toggleControls(false);
            });

            player.on('timeupdate', function () {
                if (!instance.isTeaser) return;

                var current = player.currentTime;
                var duration = player.duration;
                var effectiveEnd = (duration > 0 && endTime > duration) ? duration - 0.5 : endTime;

                // If past end, loop back to start.
                if (current >= effectiveEnd) {
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
                    player.play();
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
        player.play();
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

        // Pause any other teasers.
        for (var otherId in instances) {
            if (otherId !== id && instances[otherId].isTeaser) {
                instances[otherId].player.pause();
            }
        }

        var instance = instances[id];
        if (!instance) return;

        instance.isTeaser = false;
        instance.container.classList.remove('vt-teaser-active');
        activeId = id;

        var player = instance.player;
        player.muted = false;
        player.restart();

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
            instance.player.play();
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
