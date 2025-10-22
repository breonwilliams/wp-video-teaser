<?php
/**
 * Plugin Name: Video Teaser
 * Plugin URI: https://github.com/breonwilliams/video-teaser
 * Description: Create engaging YouTube video teasers with autoplay loop and click-to-play functionality for WordPress.
 * Version: 1.0.0
 * Author: Breon Williams
 * Author URI: https://breonwilliams.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: video-teaser
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin activation
register_activation_hook(__FILE__, 'video_teaser_activate');
function video_teaser_activate() {
    video_teaser_register_post_type();
    flush_rewrite_rules();
}

// Plugin deactivation
register_deactivation_hook(__FILE__, 'video_teaser_deactivate');
function video_teaser_deactivate() {
    flush_rewrite_rules();
}

// Load plugin textdomain
add_action('plugins_loaded', 'video_teaser_load_textdomain');
function video_teaser_load_textdomain() {
    load_plugin_textdomain('video-teaser', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

// Initialize plugin
add_action('init', 'video_teaser_init');
function video_teaser_init() {
    video_teaser_register_post_type();
    add_shortcode('video_teaser', 'video_teaser_shortcode');
}

// Register custom post type
function video_teaser_register_post_type() {
    $labels = array(
        'name' => __('Video Teasers', 'video-teaser'),
        'singular_name' => __('Video Teaser', 'video-teaser'),
        'menu_name' => __('Video Teasers', 'video-teaser'),
        'add_new' => __('Add New', 'video-teaser'),
        'add_new_item' => __('Add New Video Teaser', 'video-teaser'),
        'edit_item' => __('Edit Video Teaser', 'video-teaser'),
        'new_item' => __('New Video Teaser', 'video-teaser'),
        'view_item' => __('View Video Teaser', 'video-teaser'),
        'search_items' => __('Search Video Teasers', 'video-teaser'),
        'not_found' => __('No video teasers found', 'video-teaser'),
        'not_found_in_trash' => __('No video teasers found in Trash', 'video-teaser'),
    );

    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-video-alt3',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
    );

    register_post_type('video_teaser', $args);
}

// Add meta boxes
add_action('add_meta_boxes', 'video_teaser_add_meta_boxes');
function video_teaser_add_meta_boxes() {
    add_meta_box(
        'video_teaser_settings',
        __('Video Settings', 'video-teaser'),
        'video_teaser_settings_callback',
        'video_teaser',
        'normal',
        'high'
    );

    add_meta_box(
        'video_teaser_shortcode',
        __('Shortcode', 'video-teaser'),
        'video_teaser_shortcode_callback',
        'video_teaser',
        'side',
        'high'
    );
}

// Settings meta box callback
function video_teaser_settings_callback($post) {
    wp_nonce_field('video_teaser_save_meta', 'video_teaser_nonce');
    
    $youtube_url = get_post_meta($post->ID, '_youtube_url', true);
    $start_time = get_post_meta($post->ID, '_start_time', true);
    $end_time = get_post_meta($post->ID, '_end_time', true);
    $button_color = get_post_meta($post->ID, '_button_color', true) ?: '#ffffff';
    $icon_color = get_post_meta($post->ID, '_icon_color', true) ?: '#000000';
    ?>
    <table class="form-table">
        <tr>
            <th><label for="youtube_url"><?php _e('YouTube URL', 'video-teaser'); ?></label></th>
            <td>
                <input type="url" id="youtube_url" name="youtube_url" value="<?php echo esc_attr($youtube_url); ?>" class="regular-text" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" required />
                <p class="description"><?php _e('Enter the full YouTube video URL', 'video-teaser'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="start_time"><?php _e('Start Time (seconds)', 'video-teaser'); ?></label></th>
            <td>
                <input type="number" id="start_time" name="start_time" value="<?php echo esc_attr($start_time ?: 0); ?>" min="0" />
                <p class="description"><?php _e('When to start the teaser segment', 'video-teaser'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="end_time"><?php _e('End Time (seconds)', 'video-teaser'); ?></label></th>
            <td>
                <input type="number" id="end_time" name="end_time" value="<?php echo esc_attr($end_time ?: 10); ?>" min="1" />
                <p class="description"><?php _e('When to end the teaser segment', 'video-teaser'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="button_color"><?php _e('Play Button Color', 'video-teaser'); ?></label></th>
            <td>
                <input type="color" id="button_color" name="button_color" value="<?php echo esc_attr($button_color); ?>" />
                <p class="description"><?php _e('Background color for the play button', 'video-teaser'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="icon_color"><?php _e('Play Icon Color', 'video-teaser'); ?></label></th>
            <td>
                <input type="color" id="icon_color" name="icon_color" value="<?php echo esc_attr($icon_color); ?>" />
                <p class="description"><?php _e('Color for the play triangle icon', 'video-teaser'); ?></p>
            </td>
        </tr>
    </table>
    <?php
}

// Shortcode meta box callback
function video_teaser_shortcode_callback($post) {
    ?>
    <p><?php _e('Use this shortcode to display the video teaser:', 'video-teaser'); ?></p>
    <input type="text" value="[video_teaser id=&quot;<?php echo $post->ID; ?>&quot;]" readonly class="regular-text" onclick="this.select();" />
    <p class="description"><?php _e('Click to select and copy', 'video-teaser'); ?></p>
    <?php
}

// Save meta data
add_action('save_post', 'video_teaser_save_meta');
function video_teaser_save_meta($post_id) {
    // Only process video_teaser post type
    if (get_post_type($post_id) !== 'video_teaser') {
        return;
    }

    if (!isset($_POST['video_teaser_nonce']) || !wp_verify_nonce($_POST['video_teaser_nonce'], 'video_teaser_save_meta')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['youtube_url'])) {
        $youtube_url = sanitize_url($_POST['youtube_url']);
        if (video_teaser_validate_youtube_url($youtube_url)) {
            update_post_meta($post_id, '_youtube_url', $youtube_url);
            update_post_meta($post_id, '_video_id', video_teaser_extract_video_id($youtube_url));
        }
    }

    $start_time = isset($_POST['start_time']) ? absint($_POST['start_time']) : 0;
    $end_time = isset($_POST['end_time']) ? absint($_POST['end_time']) : 10;
    
    // Validate time ranges
    if ($start_time < 0) {
        $start_time = 0;
    }
    if ($end_time <= $start_time) {
        $end_time = $start_time + 10;
    }
    if ($end_time > 7200) { // Max 2 hours
        $end_time = 7200;
    }
    
    update_post_meta($post_id, '_start_time', $start_time);
    update_post_meta($post_id, '_end_time', $end_time);
    
    // Save button color
    if (isset($_POST['button_color'])) {
        $button_color = sanitize_hex_color($_POST['button_color']);
        if ($button_color) {
            update_post_meta($post_id, '_button_color', $button_color);
        }
    }
    
    // Save icon color
    if (isset($_POST['icon_color'])) {
        $icon_color = sanitize_hex_color($_POST['icon_color']);
        if ($icon_color) {
            update_post_meta($post_id, '_icon_color', $icon_color);
        }
    }
}

// Validate YouTube URL
function video_teaser_validate_youtube_url($url) {
    if (empty($url)) {
        return false;
    }
    
    $parsed_url = parse_url($url);
    $valid_hosts = ['www.youtube.com', 'youtube.com', 'youtu.be', 'm.youtube.com'];
    
    if (!isset($parsed_url['host']) || !in_array($parsed_url['host'], $valid_hosts)) {
        return false;
    }
    
    return video_teaser_extract_video_id($url) !== false;
}

// Extract YouTube video ID
function video_teaser_extract_video_id($url) {
    preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
    return isset($matches[1]) ? $matches[1] : false;
}


// Shortcode function
function video_teaser_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'video_teaser');

    $post_id = absint($atts['id']);
    if ($post_id <= 0) {
        return '<p>' . __('Invalid video teaser ID', 'video-teaser') . '</p>';
    }

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'video_teaser') {
        return '<p>' . __('Video teaser not found', 'video-teaser') . '</p>';
    }

    $youtube_url = get_post_meta($post_id, '_youtube_url', true);
    $video_id = get_post_meta($post_id, '_video_id', true);
    $start_time = get_post_meta($post_id, '_start_time', true) ?: 0;
    $end_time = get_post_meta($post_id, '_end_time', true) ?: 10;
    $button_color = get_post_meta($post_id, '_button_color', true) ?: '#ffffff';
    $icon_color = get_post_meta($post_id, '_icon_color', true) ?: '#000000';

    if (empty($youtube_url) || empty($video_id)) {
        return '<p>' . __('Video teaser not configured', 'video-teaser') . '</p>';
    }

    $unique_id = 'video-teaser-' . $post_id . '-' . uniqid();

    ob_start();
    ?>
    <style>
    #<?php echo esc_attr($unique_id); ?> {
        --button-color: <?php echo esc_attr($button_color); ?>;
        --icon-color: <?php echo esc_attr($icon_color); ?>;
    }
    </style>
    <div class="video-teaser-container" id="<?php echo esc_attr($unique_id); ?>" 
         data-video-id="<?php echo esc_attr($video_id); ?>" 
         data-start="<?php echo esc_attr($start_time); ?>" 
         data-end="<?php echo esc_attr($end_time); ?>">
        <div class="video-player" id="<?php echo esc_attr($unique_id); ?>-player"></div>
        <div class="video-overlay" 
             onclick="videoTeaserPlay('<?php echo esc_js($unique_id); ?>')"
             onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();videoTeaserPlay('<?php echo esc_js($unique_id); ?>')}"
             tabindex="0"
             role="button"
             aria-label="<?php esc_attr_e('Play video', 'video-teaser'); ?>">
            <div class="play-button">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none">
                    <path d="M8 5v14l11-7z" fill="currentColor"/>
                </svg>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'video_teaser_enqueue_scripts');
function video_teaser_enqueue_scripts() {
    // Always load scripts and styles on frontend for reliability
    add_action('wp_head', 'video_teaser_add_inline_styles');
    add_action('wp_footer', 'video_teaser_add_inline_scripts');
}

// Add inline CSS
function video_teaser_add_inline_styles() {
    ?>
    <style>
    .video-teaser-container {
        position: relative;
        width: 100%;
        max-width: 800px;
        margin: 0 auto;
        background: #000;
        border-radius: 12px;
        overflow: hidden;
    }
    
    .video-teaser-container .video-player {
        position: relative;
        width: 100%;
        height: 0;
        padding-bottom: 56.25%; /* 16:9 aspect ratio */
    }
    
    .video-teaser-container .video-player iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        border: none;
        pointer-events: none;
    }
    
    .video-teaser-container .video-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        z-index: 10;
        pointer-events: auto;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .video-teaser-container:hover .video-overlay {
        opacity: 1;
    }
    
    .video-teaser-container .play-button {
        width: 80px;
        height: 80px;
        background: var(--button-color, #ffffff);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.2s ease;
    }
    
    .video-teaser-container:hover .play-button {
        transform: scale(1.1);
    }
    
    .video-teaser-container .play-button svg {
        color: var(--icon-color, #000000);
        transform: translateX(2px);
    }
    
    .video-teaser-container.playing .video-overlay {
        display: none;
    }
    
    .video-teaser-container.playing .video-player iframe {
        pointer-events: auto;
    }
    
    /* Focus state for keyboard navigation */
    .video-teaser-container .video-overlay:focus {
        outline: 3px solid var(--icon-color, #000000);
        outline-offset: 2px;
    }
    
    .video-teaser-container .video-overlay:focus .play-button {
        transform: scale(1.1);
    }
    
    /* Mobile adjustments */
    @media (max-width: 768px) {
        .video-teaser-container .play-button {
            width: 64px;
            height: 64px;
        }
        
        .video-teaser-container .play-button svg {
            width: 48px;
            height: 48px;
        }
    }
    </style>
    <?php
}

// Add inline JavaScript
function video_teaser_add_inline_scripts() {
    $debug_mode = defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false';
    ?>
    <script>
    window.VideoTeaser = window.VideoTeaser || (function() {
        var videoTeaserData = {};
        var debugMode = <?php echo $debug_mode; ?>;
        
        // Debug logging (only in development)
        function log(message) {
            if (debugMode && console && console.log) {
                console.log('[VideoTeaser] ' + message);
            }
        }
        
        // Initialize video teasers with simple iframe approach
        function initializeVideoTeasers() {
            var containers = document.querySelectorAll('.video-teaser-container');
            log('Found ' + containers.length + ' containers');
            
            for (var i = 0; i < containers.length; i++) {
                var container = containers[i];
                if (!videoTeaserData[container.id]) {
                    createTeaserEmbed(container);
                }
            }
        }
        
        function createTeaserEmbed(container) {
            var videoId = container.dataset.videoId;
            var startTime = parseInt(container.dataset.start) || 0;
            var endTime = parseInt(container.dataset.end) || 10;
            var playerId = container.id + '-player';
            
            log('Creating teaser embed for video: ' + videoId);
            
            if (!videoId) {
                log('No video ID found');
                return;
            }
            
            var playerElement = document.getElementById(playerId);
            if (!playerElement) {
                log('Player element not found: ' + playerId);
                return;
            }
            
            // Create iframe with teaser parameters
            var embedUrl = 'https://www.youtube.com/embed/' + videoId + 
                          '?autoplay=1&mute=1&controls=0&rel=0' +
                          '&start=' + startTime + '&end=' + endTime +
                          '&loop=1&playlist=' + videoId + '&modestbranding=1';
            
            playerElement.innerHTML = '<iframe width="100%" height="100%" src="' + embedUrl + 
                                     '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
            
            videoTeaserData[container.id] = {
                videoId: videoId,
                startTime: startTime,
                endTime: endTime,
                isTeaser: true
            };
            
            log('Teaser embed created successfully');
        }
        
        // Public function for click handler - switch to full video
        function videoTeaserPlay(containerId) {
            log('Play clicked for: ' + containerId);
            var container = document.getElementById(containerId);
            var data = videoTeaserData[containerId];
            
            if (!data) {
                log('No video data found');
                return;
            }
            
            data.isTeaser = false;
            container.classList.add('playing');
            
            var playerId = containerId + '-player';
            var playerElement = document.getElementById(playerId);
            
            if (playerElement) {
                // Switch to full video with controls
                var fullVideoUrl = 'https://www.youtube.com/embed/' + data.videoId + 
                                  '?autoplay=1&controls=1&showinfo=1&rel=0&modestbranding=1';
                
                playerElement.innerHTML = '<iframe width="100%" height="100%" src="' + fullVideoUrl + 
                                         '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>';
                
                log('Switched to full video');
            }
        }
        
        // Initialize when DOM is ready
        function init() {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    log('DOM ready - initializing');
                    initializeVideoTeasers();
                });
            } else {
                log('DOM already ready - initializing');
                initializeVideoTeasers();
            }
        }
        
        // Public API
        return {
            play: videoTeaserPlay,
            init: init
        };
    })();
    
    // Initialize immediately
    VideoTeaser.init();
    
    // Global function for backward compatibility
    window.videoTeaserPlay = VideoTeaser.play;
    </script>
    <?php
}
?>