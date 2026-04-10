<?php
/**
 * Script and style enqueue management.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Video_Teaser_Assets {

    /**
     * Whether the current page needs preconnect hints.
     *
     * @var bool
     */
    private static $needs_preconnect = false;

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'template_redirect', array( $this, 'detect_video_teaser_content' ) );
        add_filter( 'wp_resource_hints', array( $this, 'resource_hints' ), 10, 2 );
    }

    /**
     * Detect if the current page contains a video teaser.
     *
     * This runs on template_redirect (before wp_head) so we can add
     * preconnect hints before the shortcode actually enqueues scripts.
     *
     * Checks multiple sources to catch video teasers rendered via:
     * - Direct shortcode in post content
     * - Page builders (Elementor, Beaver Builder, Divi, etc.)
     * - AI Section Builder (Promptless)
     * - Any plugin storing "video_teaser" references in post meta
     */
    public function detect_video_teaser_content() {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_queried_object();
        if ( ! $post ) {
            return;
        }

        // Check 1: Direct shortcode in post content.
        if ( has_shortcode( $post->post_content, 'video_teaser' ) ) {
            self::$needs_preconnect = true;
            return;
        }

        // Check 2: Scan post meta for any video_teaser reference.
        // This catches all page builders that store video_teaser shortcodes or IDs.
        $all_meta = get_post_meta( $post->ID );
        foreach ( $all_meta as $values ) {
            foreach ( (array) $values as $value ) {
                if ( is_string( $value ) && strpos( $value, 'video_teaser' ) !== false ) {
                    self::$needs_preconnect = true;
                    return;
                }
            }
        }

        // Check 3: Allow explicit override via filter (for edge cases).
        if ( apply_filters( 'video_teaser_needs_preconnect', false, $post ) ) {
            self::$needs_preconnect = true;
        }
    }

    /**
     * Enqueue admin assets on the video_teaser edit screen.
     *
     * @param string $hook_suffix Current admin page.
     */
    public function admin_assets( $hook_suffix ) {
        $screen = get_current_screen();
        if ( ! $screen || $screen->post_type !== Video_Teaser_Post_Type::SLUG ) {
            return;
        }

        // Only on add/edit screens.
        if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
            return;
        }

        // WordPress color picker.
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // WordPress media uploader.
        wp_enqueue_media();

        // Plyr vendor (for admin preview).
        wp_enqueue_style( 'plyr', VIDEO_TEASER_URL . 'assets/vendor/plyr.css', array(), '3.7.8' );
        wp_enqueue_script( 'plyr', VIDEO_TEASER_URL . 'assets/vendor/plyr.min.js', array(), '3.7.8', true );

        // Plugin admin styles (minified).
        wp_enqueue_style(
            'vt-admin',
            VIDEO_TEASER_URL . 'assets/css/admin.min.css',
            array( 'wp-color-picker', 'plyr' ),
            VIDEO_TEASER_VERSION
        );

        // Plugin admin scripts (minified).
        wp_enqueue_script(
            'vt-admin',
            VIDEO_TEASER_URL . 'assets/js/admin.min.js',
            array( 'jquery', 'wp-color-picker', 'plyr' ),
            VIDEO_TEASER_VERSION,
            true
        );

        // Build media URL for preview if available.
        $media_url = '';
        $post_id = isset( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : 0;
        if ( $post_id ) {
            $media_id = get_post_meta( $post_id, '_vt_media_id', true );
            if ( $media_id ) {
                $media_url = wp_get_attachment_url( $media_id );
            }
        }

        wp_localize_script( 'vt-admin', 'vtAdmin', array(
            'mediaTitle'  => __( 'Select Video', 'video-teaser' ),
            'mediaButton' => __( 'Use this video', 'video-teaser' ),
            'changeLabel' => __( 'Change Video', 'video-teaser' ),
            'selectLabel' => __( 'Select Video', 'video-teaser' ),
            'mediaUrl'          => $media_url,
            'posterTitle'       => __( 'Select Poster Image', 'video-teaser' ),
            'posterButton'      => __( 'Use this image', 'video-teaser' ),
            'posterChangeLabel' => __( 'Change Image', 'video-teaser' ),
            'posterSelectLabel' => __( 'Select Image', 'video-teaser' ),
        ) );
    }

    /**
     * Enqueue frontend assets. Called by the shortcode on first render.
     */
    public static function enqueue_frontend() {
        // Plyr vendor.
        wp_enqueue_style(
            'plyr',
            VIDEO_TEASER_URL . 'assets/vendor/plyr.css',
            array(),
            '3.7.8'
        );

        wp_enqueue_script(
            'plyr',
            VIDEO_TEASER_URL . 'assets/vendor/plyr.min.js',
            array(),
            '3.7.8',
            true
        );

        // Plugin frontend styles (minified).
        wp_enqueue_style(
            'vt-frontend',
            VIDEO_TEASER_URL . 'assets/css/frontend.min.css',
            array( 'plyr' ),
            VIDEO_TEASER_VERSION
        );

        // Plugin frontend scripts (minified).
        wp_enqueue_script(
            'vt-frontend',
            VIDEO_TEASER_URL . 'assets/js/frontend.min.js',
            array( 'plyr' ),
            VIDEO_TEASER_VERSION,
            true
        );

        wp_localize_script( 'vt-frontend', 'vtFrontend', array(
            'debug' => defined( 'WP_DEBUG' ) && WP_DEBUG,
        ) );
    }

    /**
     * Output preconnect hints for YouTube and Vimeo.
     */
    /**
     * Add preconnect and dns-prefetch hints only when video scripts are enqueued.
     *
     * @param array  $urls          URLs to print for resource hints.
     * @param string $relation_type The relation type the URLs are printed for.
     * @return array
     */
    public function resource_hints( $urls, $relation_type ) {
        // Check if Plyr is enqueued (admin) or if we detected video teaser content (frontend).
        if ( ! wp_script_is( 'plyr', 'enqueued' ) && ! self::$needs_preconnect ) {
            return $urls;
        }

        if ( 'preconnect' === $relation_type ) {
            // Plyr CDN - loads SVG sprite for player controls.
            $urls[] = array(
                'href' => 'https://cdn.plyr.io',
                'crossorigin' => 'anonymous',
            );
            $urls[] = array(
                'href' => 'https://www.youtube.com',
                'crossorigin' => 'anonymous',
            );
            $urls[] = array(
                'href' => 'https://i.vimeocdn.com',
                'crossorigin' => 'anonymous',
            );
        }

        if ( 'dns-prefetch' === $relation_type ) {
            $urls[] = '//www.youtube.com';
            $urls[] = '//player.vimeo.com';
        }

        return $urls;
    }
}
