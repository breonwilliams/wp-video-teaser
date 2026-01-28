/**
 * Video Teaser — Admin Scripts
 *
 * Handles tab switching, media library modal, teaser toggle,
 * color picker, shortcode copy, and admin video preview.
 *
 * @package Video_Teaser
 */
(function ($) {
    'use strict';

    var previewPlayer = null;

    /* -------------------------------------------
       Tab Switching
       ------------------------------------------- */
    function initTabs() {
        var $radios = $('input[name="vt_source_type_radio"]');
        var $panels = $('.vt-tab-panel');
        var $sourceInput = $('#vt_source_type');

        $radios.on('change', function () {
            var tab = $(this).val();

            $panels.removeClass('vt-tab-panel--active');
            $panels.filter('[data-panel="' + tab + '"]').addClass('vt-tab-panel--active');

            $sourceInput.val(tab);
            updatePreview();
        });
    }

    /* -------------------------------------------
       Media Library Modal
       ------------------------------------------- */
    function initMediaUploader() {
        var frame;
        var $selectBtn = $('#vt-media-select');
        var $preview = $('#vt-media-preview');
        var $title = $('#vt-media-title');
        var $input = $('#vt_media_id');
        var $removeBtn = $('#vt-media-remove');

        $selectBtn.on('click', function (e) {
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

            frame.on('select', function () {
                var attachment = frame.state().get('selection').first().toJSON();
                $input.val(attachment.id);
                $title.text(attachment.title || attachment.filename);
                $preview.show();
                $selectBtn.find('.vt-media-btn-label').text(vtAdmin.changeLabel);
                vtAdmin.mediaUrl = attachment.url;
                updatePreview();
            });

            frame.open();
        });

        $removeBtn.on('click', function (e) {
            e.preventDefault();
            $input.val('');
            $title.text('');
            $preview.hide();
            $selectBtn.find('.vt-media-btn-label').text(vtAdmin.selectLabel);
            vtAdmin.mediaUrl = '';
            updatePreview();
        });
    }

    /* -------------------------------------------
       Teaser Toggle
       ------------------------------------------- */
    function initTeaserToggle() {
        var $toggle = $('#vt_teaser_enabled');
        var $fields = $('#vt-teaser-fields');
        var $startTime = $('#vt_start_time');
        var $endTime = $('#vt_end_time');

        $toggle.on('change', function () {
            if (this.checked) {
                $fields.slideDown(150);
            } else {
                $fields.slideUp(150);
            }
        });

        $startTime.on('change', function () {
            var start = parseInt(this.value, 10) || 0;
            var end = parseInt($endTime.val(), 10) || 0;
            if (end <= start) {
                $endTime.val(start + 1);
            }
        });

        $endTime.on('change', function () {
            var start = parseInt($startTime.val(), 10) || 0;
            var end = parseInt(this.value, 10) || 0;
            if (end <= start) {
                this.value = start + 1;
            }
        });
    }

    /* -------------------------------------------
       Color Pickers
       ------------------------------------------- */
    function initColorPickers() {
        $('#vt_button_color').wpColorPicker({
            change: function (event, ui) {
                applyPreviewColor(ui.color.toString());
            },
            clear: function () {
                applyPreviewColor('#00b3ff');
            },
        });

        // Apply the current saved color on load.
        var initialColor = $('#vt_button_color').val() || '#00b3ff';
        applyPreviewColor(initialColor);
    }

    function applyPreviewColor(color) {
        var el = document.getElementById('vt-admin-preview');
        if (el) {
            el.style.setProperty('--plyr-color-main', color);
        }
    }

    /* -------------------------------------------
       Shortcode Copy
       ------------------------------------------- */
    function initShortcodeCopy() {
        var $copyBtn = $('#vt-shortcode-copy');
        var $input = $('#vt-shortcode-input');
        var $feedback = $('#vt-copy-feedback');

        $copyBtn.on('click', function () {
            $input[0].select();

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText($input.val()).then(function () {
                    showFeedback();
                });
            } else {
                document.execCommand('copy');
                showFeedback();
            }
        });

        function showFeedback() {
            $feedback.fadeIn(150);
            setTimeout(function () {
                $feedback.fadeOut(150);
            }, 1500);
        }
    }

    /* -------------------------------------------
       Admin Video Preview
       ------------------------------------------- */
    var previewDebounce = null;

    function getCurrentSource() {
        var type = $('#vt_source_type').val() || 'youtube';
        var result = { type: type, url: '', provider: '' };

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

    function extractYouTubeId(url) {
        var match = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/);
        return match ? match[1] : '';
    }

    function extractVimeoId(url) {
        var match = url.match(/vimeo\.com\/(?:video\/)?(\d+)/);
        return match ? match[1] : '';
    }

    function destroyPreview() {
        if (previewPlayer) {
            try { previewPlayer.destroy(); } catch (e) {}
            previewPlayer = null;
        }
    }

    function updatePreview() {
        if (previewDebounce) {
            clearTimeout(previewDebounce);
        }
        previewDebounce = setTimeout(doUpdatePreview, 300);
    }

    function doUpdatePreview() {
        var $container = $('#vt-admin-preview');
        var source = getCurrentSource();
        var emptyHtml = '<div class="vt-preview-empty"><span class="dashicons dashicons-video-alt3"></span><p>Enter a video URL to see preview</p></div>';

        destroyPreview();

        if (!source.url) {
            $container.html(emptyHtml);
            return;
        }

        if (source.provider === 'youtube') {
            var ytId = extractYouTubeId(source.url);
            if (!ytId) {
                $container.html(emptyHtml);
                return;
            }
            var ytDiv = document.createElement('div');
            ytDiv.id = 'vt-preview-player';
            ytDiv.setAttribute('data-plyr-provider', 'youtube');
            ytDiv.setAttribute('data-plyr-embed-id', ytId);
            $container.empty().append(ytDiv);
            previewPlayer = new Plyr('#vt-preview-player', { muted: true });
        } else if (source.provider === 'vimeo') {
            var vmId = extractVimeoId(source.url);
            if (!vmId) {
                $container.html(emptyHtml);
                return;
            }
            var vmDiv = document.createElement('div');
            vmDiv.id = 'vt-preview-player';
            vmDiv.setAttribute('data-plyr-provider', 'vimeo');
            vmDiv.setAttribute('data-plyr-embed-id', vmId);
            $container.empty().append(vmDiv);
            previewPlayer = new Plyr('#vt-preview-player', { muted: true });
        } else if (source.type === 'external' || source.type === 'media_library') {
            var video = document.createElement('video');
            video.id = 'vt-preview-player';
            video.src = source.url;
            video.crossOrigin = 'anonymous';
            video.playsInline = true;
            $container.empty().append(video);
            previewPlayer = new Plyr('#vt-preview-player', { muted: true });
        } else {
            $container.html(emptyHtml);
        }
    }

    function initAspectRatio() {
        var $select = $('#vt_aspect_ratio');
        var $preview = $('#vt-admin-preview');

        function applyRatio() {
            var val = $select.val() || '16:9';
            if (val === 'auto') {
                $preview.css('aspect-ratio', '');
            } else {
                $preview.css('aspect-ratio', val.replace(':', ' / '));
            }
        }

        $select.on('change', applyRatio);
        applyRatio();
    }

    function initPreview() {
        // Listen for URL input changes (debounced via updatePreview).
        $('#vt_youtube_url, #vt_vimeo_url, #vt_external_url').on('input', updatePreview);

        // Initial render.
        updatePreview();
    }

    /* -------------------------------------------
       Initialize
       ------------------------------------------- */
    $(function () {
        initTabs();
        initMediaUploader();
        initTeaserToggle();
        initColorPickers();
        initShortcodeCopy();
        initAspectRatio();
        initPreview();
    });
})(jQuery);
