/**
 * Video Teaser — Frontend Scripts
 *
 * Initializes Plyr players, manages teaser looping via timeupdate,
 * handles play/revert logic, and enforces one-active-at-a-time.
 *
 * @package Video_Teaser
 */

/// <reference path="./types/plyr.d.ts" />

interface VideoTeaserConfig {
  debug: boolean;
}

interface TeaserInstance {
  player: Plyr;
  container: HTMLElement;
  sourceType: string;
  teaserEnabled: boolean;
  startTime: number;
  endTime: number;
  isTeaser: boolean;
}

declare const vtFrontend: VideoTeaserConfig | undefined;

const VideoTeaser = ((): {
  play: (id: string) => void;
  revert: (id: string) => void;
  instances: Map<string, TeaserInstance>;
} => {
  const debug = typeof vtFrontend !== 'undefined' && vtFrontend.debug;
  const instances: Map<string, TeaserInstance> = new Map();
  let activeId: string | null = null;

  function log(msg: string): void {
    if (debug && console && console.log) {
      console.log(`[VideoTeaser] ${msg}`);
    }
  }

  async function safePlay(player: Plyr): Promise<void> {
    try {
      await player.play();
    } catch (e) {
      log(`Play failed: ${(e as Error).message}`);
    }
  }

  /**
   * Initialize all containers on the page.
   */
  function initAll(): void {
    const containers = document.querySelectorAll<HTMLElement>('.vt-container');
    log(`Found ${containers.length} container(s)`);
    containers.forEach(initContainer);
  }

  /**
   * Initialize a single video container.
   */
  function initContainer(container: HTMLElement): void {
    const id = container.id;
    if (instances.has(id)) return;

    const sourceType = container.dataset.vtSource || '';
    const teaserEnabled = container.dataset.vtTeaser === '1';
    let startTime = parseInt(container.dataset.vtStart || '0', 10);
    let endTime = parseInt(container.dataset.vtEnd || '10', 10);
    const ratioAttr = container.dataset.vtRatio || '16:9';

    if (teaserEnabled && startTime >= endTime) {
      log(`${id}: startTime >= endTime, using defaults`);
      startTime = 0;
      endTime = 10;
    }

    const playerEl = container.querySelector<HTMLVideoElement | HTMLDivElement>('.vt-player');
    if (!playerEl) {
      log(`No .vt-player found in ${id}`);
      return;
    }

    // Detect aspect ratio before Plyr initialization for self-hosted videos.
    let detectedRatio: string | null = null;
    if (ratioAttr === 'auto' && playerEl instanceof HTMLVideoElement) {
      if (playerEl.videoWidth && playerEl.videoHeight) {
        detectedRatio = `${playerEl.videoWidth}:${playerEl.videoHeight}`;
        log(`${id}: Pre-detected ratio ${detectedRatio}`);
      }
    }

    // Determine the ratio to use for Plyr initialization.
    let plyrRatio: string;
    if (detectedRatio) {
      plyrRatio = detectedRatio;
    } else if (ratioAttr !== 'auto') {
      plyrRatio = ratioAttr;
    } else {
      plyrRatio = '16:9'; // Fallback for auto when metadata not yet loaded
    }

    const plyrOptions: PlyrOptions = {
      controls: [
        'play-large',
        'play',
        'progress',
        'current-time',
        'mute',
        'volume',
        'captions',
        'settings',
        'pip',
        'airplay',
        'fullscreen',
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

    let player: Plyr;
    try {
      player = new Plyr(playerEl, plyrOptions);
    } catch (e) {
      log(`Plyr init failed for ${id}: ${(e as Error).message}`);
      return;
    }

    // Listen for metadata in case it loads after Plyr init.
    if (ratioAttr === 'auto' && playerEl instanceof HTMLVideoElement && !detectedRatio) {
      playerEl.addEventListener('loadedmetadata', () => {
        const w = playerEl.videoWidth;
        const h = playerEl.videoHeight;
        if (w && h) {
          player.ratio = `${w}:${h}`;
          log(`${id}: Post-detected ratio ${w}:${h}`);
        }
      });
    }

    const instance: TeaserInstance = {
      player,
      container,
      sourceType,
      teaserEnabled,
      startTime,
      endTime,
      isTeaser: teaserEnabled,
    };

    instances.set(id, instance);

    // Setup teaser mode if enabled.
    if (teaserEnabled) {
      setupTeaserMode(instance, id);
    }

    // When an unmuted video starts, revert the previous unmuted video.
    player.on('play', () => {
      if (instance.isTeaser) return;

      if (activeId && activeId !== id) {
        const prev = instances.get(activeId);
        if (prev) revertToTeaser(activeId);
      }
      activeId = id;
    });

    log(`Initialized ${id} (${sourceType})`);
  }

  /**
   * Setup teaser mode for an instance.
   */
  function setupTeaserMode(instance: TeaserInstance, id: string): void {
    const { player, container, startTime, endTime } = instance;

    // Create play-full overlay button.
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'vt-play-full';
    btn.setAttribute('aria-label', 'Play full video');

    const hasSpritePlay = !!document.getElementById('plyr-play');
    const svgContent = hasSpritePlay
      ? '<svg aria-hidden="true" focusable="false"><use xlink:href="#plyr-play"></use></svg>'
      : '<svg aria-hidden="true" focusable="false" viewBox="0 0 18 18"><path d="M15.562 8.1L3.87.573c-.344-.215-.79.042-.79.437v15.04c0 .395.446.652.79.437L15.563 8.9c.344-.215.344-.753 0-.8z" fill="currentColor"/></svg>';

    btn.innerHTML = svgContent + '<span class="plyr__sr-only">Play</span>';
    container.appendChild(btn);
    container.classList.add('vt-teaser-active');

    btn.addEventListener('click', (e: Event) => {
      e.stopPropagation();
      playFull(id);
    });

    // Teaser loop via timeupdate.
    let isSeeking = false;
    player.on('seeked', () => {
      isSeeking = false;
    });

    player.on('ready', () => {
      log(`${id} player ready`);
      seekToStart(instance);
      player.toggleControls(false);
    });

    player.on('timeupdate', () => {
      if (!instance.isTeaser || isSeeking) return;

      const current = player.currentTime;
      const duration = player.duration;
      const effectiveEnd = duration > 0 && endTime > duration ? duration - 0.5 : endTime;

      if (current >= effectiveEnd) {
        isSeeking = true;
        player.currentTime = startTime;
      }
    });

    player.on('canplay', () => {
      if (instance.isTeaser) {
        const duration = player.duration;
        if (duration > 0 && instance.startTime >= duration) {
          instance.startTime = 0;
        }
        seekToStart(instance);
      }
    });

    player.on('ended', () => {
      if (instance.isTeaser) {
        player.currentTime = instance.startTime;
        safePlay(player);
      }
    });
  }

  /**
   * Seek to teaser start position.
   */
  function seekToStart(instance: TeaserInstance): void {
    const { player, startTime, endTime } = instance;
    if (player.currentTime < startTime || player.currentTime >= endTime) {
      player.currentTime = startTime;
    }
    player.muted = true;
    safePlay(player);
  }

  /**
   * Switch to full video mode.
   */
  function playFull(id: string): void {
    log(`Play full: ${id}`);

    // Revert any currently active video.
    if (activeId && activeId !== id) {
      const prev = instances.get(activeId);
      if (prev) revertToTeaser(activeId);
    }

    const instance = instances.get(id);
    if (!instance) return;

    instance.isTeaser = false;
    instance.container.classList.remove('vt-teaser-active');
    activeId = id;

    const { player } = instance;
    player.muted = false;
    try {
      player.restart();
    } catch {
      player.currentTime = 0;
      safePlay(player);
    }

    log(`Now playing full: ${id}`);
  }

  /**
   * Revert to teaser mode.
   */
  function revertToTeaser(id: string): void {
    log(`Reverting to teaser: ${id}`);

    const instance = instances.get(id);
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

  /**
   * DOM Ready handler.
   */
  function onReady(): void {
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

  // Public API.
  return {
    play: playFull,
    revert: revertToTeaser,
    instances,
  };
})();

// Expose to global scope.
(window as unknown as { VideoTeaser: typeof VideoTeaser }).VideoTeaser = VideoTeaser;
