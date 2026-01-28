/**
 * Video Teaser — Admin Scripts
 *
 * Handles tab switching, media library modal, teaser toggle,
 * color picker, shortcode copy, and admin video preview.
 *
 * @package Video_Teaser
 */

/// <reference path="./types/plyr.d.ts" />

interface VtAdminConfig {
  mediaTitle: string;
  mediaButton: string;
  changeLabel: string;
  selectLabel: string;
  mediaUrl: string;
  posterTitle: string;
  posterButton: string;
  posterChangeLabel: string;
  posterSelectLabel: string;
}

interface WpMedia {
  (options: {
    title: string;
    button: { text: string };
    library: { type: string };
    multiple: boolean;
  }): WpMediaFrame;
}

interface WpMediaFrame {
  open(): void;
  on(event: string, callback: () => void): void;
  state(): {
    get(key: string): {
      first(): {
        toJSON(): {
          id: number;
          title: string;
          filename: string;
          url: string;
          sizes?: {
            medium?: { url: string };
          };
        };
      };
    };
  };
}

interface WpColorPickerOptions {
  change?: (event: Event, ui: { color: { toString(): string } }) => void;
  clear?: () => void;
}

interface JQueryStatic {
  (selector: string | HTMLElement | (() => void)): JQuery;
}

interface JQuery {
  val(): string;
  val(value: string): JQuery;
  text(): string;
  text(value: string): JQuery;
  attr(name: string): string;
  attr(name: string, value: string): JQuery;
  on(event: string, callback: (e: Event) => void): JQuery;
  show(): JQuery;
  hide(): JQuery;
  css(property: string, value: string): JQuery;
  find(selector: string): JQuery;
  filter(selector: string): JQuery;
  next(selector: string): JQuery;
  addClass(className: string): JQuery;
  removeClass(className: string): JQuery;
  empty(): JQuery;
  append(element: HTMLElement): JQuery;
  insertAfter(element: JQuery): JQuery;
  slideDown(duration: number): JQuery;
  slideUp(duration: number): JQuery;
  fadeIn(duration: number): JQuery;
  fadeOut(duration: number): JQuery;
  wpColorPicker(options: WpColorPickerOptions): JQuery;
  length: number;
  0: HTMLInputElement;
}

declare const jQuery: JQueryStatic;
declare const wp: { media: WpMedia };
declare const vtAdmin: VtAdminConfig;

(($: JQueryStatic) => {
  'use strict';

  let previewPlayer: Plyr | null = null;
  let previewDebounce: ReturnType<typeof setTimeout> | null = null;

  /**
   * Tab Switching
   */
  function initTabs(): void {
    const $sourceSelect = $('#vt_source_type');
    const $panels = $('.vt-tab-panel');

    $sourceSelect.on('change', () => {
      const tab = $sourceSelect.val();
      $panels.removeClass('vt-tab-panel--active');
      $panels.filter(`[data-panel="${tab}"]`).addClass('vt-tab-panel--active');
      updatePreview();
    });
  }

  /**
   * Media Library Modal
   */
  function initMediaUploader(): void {
    let frame: WpMediaFrame | null = null;
    const $selectBtn = $('#vt-media-select');
    const $preview = $('#vt-media-preview');
    const $title = $('#vt-media-title');
    const $input = $('#vt_media_id');
    const $removeBtn = $('#vt-media-remove');

    $selectBtn.on('click', (e: Event) => {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: vtAdmin.mediaTitle,
        button: { text: vtAdmin.mediaButton },
        library: { type: 'video' },
        multiple: false,
      });

      frame.on('select', () => {
        const attachment = frame!.state().get('selection').first().toJSON();
        $input.val(String(attachment.id));
        $title.text(attachment.title || attachment.filename);
        $preview.show();
        $selectBtn.find('.vt-media-btn-label').text(vtAdmin.changeLabel);
        vtAdmin.mediaUrl = attachment.url;
        updatePreview();
      });

      frame.open();
    });

    $removeBtn.on('click', (e: Event) => {
      e.preventDefault();
      $input.val('');
      $title.text('');
      $preview.hide();
      $selectBtn.find('.vt-media-btn-label').text(vtAdmin.selectLabel);
      vtAdmin.mediaUrl = '';
      updatePreview();
    });
  }

  /**
   * Poster Image Picker
   */
  function initPosterPicker(): void {
    let frame: WpMediaFrame | null = null;
    const $selectBtn = $('#vt-poster-select');
    const $preview = $('#vt-poster-preview');
    const $thumb = $('#vt-poster-thumb');
    const $input = $('#vt_poster_image');
    const $removeBtn = $('#vt-poster-remove');

    $selectBtn.on('click', (e: Event) => {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: vtAdmin.posterTitle,
        button: { text: vtAdmin.posterButton },
        library: { type: 'image' },
        multiple: false,
      });

      frame.on('select', () => {
        const attachment = frame!.state().get('selection').first().toJSON();
        const url =
          attachment.sizes && attachment.sizes.medium
            ? attachment.sizes.medium.url
            : attachment.url;
        $input.val(String(attachment.id));
        $thumb.attr('src', url);
        $preview.show();
        $selectBtn.find('.vt-poster-btn-label').text(vtAdmin.posterChangeLabel);
      });

      frame.open();
    });

    $removeBtn.on('click', (e: Event) => {
      e.preventDefault();
      $input.val('');
      $thumb.attr('src', '');
      $preview.hide();
      $selectBtn.find('.vt-poster-btn-label').text(vtAdmin.posterSelectLabel);
    });
  }

  /**
   * Teaser Toggle
   */
  function initTeaserToggle(): void {
    const $toggle = $('#vt_teaser_enabled');
    const $fields = $('#vt-teaser-fields');
    const $startTime = $('#vt_start_time');
    const $endTime = $('#vt_end_time');

    $toggle.on('change', (e: Event) => {
      const checkbox = e.target as HTMLInputElement;
      if (checkbox.checked) {
        $fields.slideDown(150);
      } else {
        $fields.slideUp(150);
      }
    });

    $startTime.on('change', (e: Event) => {
      const input = e.target as HTMLInputElement;
      const start = parseInt(input.value, 10) || 0;
      const end = parseInt($endTime.val(), 10) || 0;
      if (end <= start) {
        $endTime.val(String(start + 1));
      }
    });

    $endTime.on('change', (e: Event) => {
      const input = e.target as HTMLInputElement;
      const start = parseInt($startTime.val(), 10) || 0;
      const end = parseInt(input.value, 10) || 0;
      if (end <= start) {
        input.value = String(start + 1);
      }
    });
  }

  /**
   * Color Pickers
   */
  function initColorPickers(): void {
    $('#vt_button_color').wpColorPicker({
      change: (_event: Event, ui: { color: { toString(): string } }) => {
        applyPreviewColor(ui.color.toString());
      },
      clear: () => {
        applyPreviewColor('#00b3ff');
      },
    });

    // Apply the current saved color on load.
    const initialColor = $('#vt_button_color').val() || '#00b3ff';
    applyPreviewColor(initialColor);
  }

  function applyPreviewColor(color: string): void {
    const el = document.getElementById('vt-admin-preview');
    if (el) {
      el.style.setProperty('--plyr-color-main', color);
    }
  }

  /**
   * Shortcode Copy
   */
  function initShortcodeCopy(): void {
    const $copyBtn = $('#vt-shortcode-copy');
    const $input = $('#vt-shortcode-input');
    const $feedback = $('#vt-copy-feedback');

    $copyBtn.on('click', () => {
      $input[0].select();

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText($input.val()).then(() => {
          showFeedback();
        });
      } else {
        document.execCommand('copy');
        showFeedback();
      }
    });

    function showFeedback(): void {
      $feedback.fadeIn(150);
      setTimeout(() => {
        $feedback.fadeOut(150);
      }, 1500);
    }
  }

  /**
   * Admin Video Preview
   */
  interface SourceInfo {
    type: string;
    url: string;
    provider: string;
  }

  function getCurrentSource(): SourceInfo {
    const type = $('#vt_source_type').val() || 'youtube';
    const result: SourceInfo = { type, url: '', provider: '' };

    if (type === 'youtube') {
      result.url = $('#vt_youtube_url').val();
      result.provider = 'youtube';
    } else if (type === 'vimeo') {
      result.url = $('#vt_vimeo_url').val();
      result.provider = 'vimeo';
    } else if (type === 'external') {
      result.url = $('#vt_external_url').val();
    } else if (type === 'media_library') {
      result.url = vtAdmin.mediaUrl || '';
    }

    return result;
  }

  function extractYouTubeId(url: string): string {
    const match = url.match(
      /(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/
    );
    return match ? match[1] : '';
  }

  function extractVimeoId(url: string): string {
    const match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
    return match ? match[1] : '';
  }

  function destroyPreview(): void {
    if (previewPlayer) {
      try {
        previewPlayer.destroy();
      } catch {
        // Ignore errors during destroy
      }
      previewPlayer = null;
    }
  }

  function updatePreview(): void {
    if (previewDebounce) {
      clearTimeout(previewDebounce);
    }
    previewDebounce = setTimeout(doUpdatePreview, 300);
  }

  function showUrlError(msg: string): void {
    const source = getCurrentSource();
    let inputId = '';
    if (source.type === 'youtube') inputId = 'vt_youtube_url';
    else if (source.type === 'vimeo') inputId = 'vt_vimeo_url';
    else if (source.type === 'external') inputId = 'vt_external_url';
    if (!inputId) return;

    const $input = $(`#${inputId}`);
    let $error = $input.next('.vt-url-error');
    if (!$error.length) {
      const errorSpan = document.createElement('span');
      errorSpan.className = 'vt-url-error';
      $error = $(errorSpan);
      $error.insertAfter($input);
    }
    $error.text(msg).show();
  }

  function hideUrlErrors(): void {
    $('.vt-url-error').hide();
  }

  function doUpdatePreview(): void {
    const $container = $('#vt-admin-preview');
    const source = getCurrentSource();

    destroyPreview();
    hideUrlErrors();

    if (!source.url) {
      $container.empty().hide();
      return;
    }

    if (source.provider === 'youtube') {
      const ytId = extractYouTubeId(source.url);
      if (!ytId) {
        $container.empty().hide();
        showUrlError('Could not detect a valid video ID from this URL.');
        return;
      }
      const ytDiv = document.createElement('div');
      ytDiv.id = 'vt-preview-player';
      ytDiv.setAttribute('data-plyr-provider', 'youtube');
      ytDiv.setAttribute('data-plyr-embed-id', ytId);
      $container.empty().append(ytDiv).show();
      previewPlayer = new Plyr('#vt-preview-player', { muted: true });
    } else if (source.provider === 'vimeo') {
      const vmId = extractVimeoId(source.url);
      if (!vmId) {
        $container.empty().hide();
        showUrlError('Could not detect a valid video ID from this URL.');
        return;
      }
      const vmDiv = document.createElement('div');
      vmDiv.id = 'vt-preview-player';
      vmDiv.setAttribute('data-plyr-provider', 'vimeo');
      vmDiv.setAttribute('data-plyr-embed-id', vmId);
      $container.empty().append(vmDiv).show();
      previewPlayer = new Plyr('#vt-preview-player', { muted: true });
    } else if (source.type === 'external' || source.type === 'media_library') {
      const video = document.createElement('video');
      video.id = 'vt-preview-player';
      video.src = source.url;
      video.crossOrigin = 'anonymous';
      video.playsInline = true;
      $(video).on('error', () => {
        $container.empty().hide();
        showUrlError('Video could not be loaded. Please check the URL.');
      });
      $container.empty().append(video).show();
      previewPlayer = new Plyr('#vt-preview-player', { muted: true });
    } else {
      $container.empty().hide();
    }
  }

  function initAspectRatio(): void {
    const $select = $('#vt_aspect_ratio');
    const $preview = $('#vt-admin-preview');

    function applyRatio(): void {
      const val = $select.val() || '16:9';
      if (val === 'auto') {
        $preview.css('aspect-ratio', '');
      } else {
        $preview.css('aspect-ratio', val.replace(':', ' / '));
      }
    }

    $select.on('change', applyRatio);
    applyRatio();
  }

  function initPreview(): void {
    // Listen for URL input changes.
    $('#vt_youtube_url, #vt_vimeo_url, #vt_external_url').on('input', updatePreview);

    // Initial render.
    updatePreview();
  }

  /**
   * Initialize all admin functionality.
   */
  $(() => {
    initTabs();
    initMediaUploader();
    initPosterPicker();
    initTeaserToggle();
    initColorPickers();
    initShortcodeCopy();
    initAspectRatio();
    initPreview();
  });
})(jQuery);
