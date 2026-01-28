<?php
/**
 * Main plugin bootstrap class.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Video_Teaser {

    /**
     * Singleton instance.
     *
     * @var Video_Teaser|null
     */
    private static $instance = null;

    /**
     * Get the singleton instance.
     *
     * @return Video_Teaser
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Private constructor.
     */
    private function __construct() {
        $this->load_includes();
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Load required class files.
     */
    private function load_includes() {
        require_once VIDEO_TEASER_PATH . 'includes/class-video-source.php';
        require_once VIDEO_TEASER_PATH . 'includes/class-post-type.php';
        require_once VIDEO_TEASER_PATH . 'includes/class-meta-boxes.php';
        require_once VIDEO_TEASER_PATH . 'includes/class-shortcode.php';
        require_once VIDEO_TEASER_PATH . 'includes/class-assets.php';
        require_once VIDEO_TEASER_PATH . 'includes/class-updater.php';
    }

    /**
     * Initialize components.
     */
    private function init_components() {
        ( new Video_Teaser_Post_Type() )->init();
        ( new Video_Teaser_Meta_Boxes() )->init();
        ( new Video_Teaser_Shortcode() )->init();
        ( new Video_Teaser_Assets() )->init();
        ( new Video_Teaser_Updater() )->init();
    }

    /**
     * Register activation and deactivation hooks.
     */
    private function register_hooks() {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
    }

    /**
     * Load plugin text domain for translations.
     */
    public function load_textdomain() {
        load_plugin_textdomain( 'video-teaser', false, dirname( plugin_basename( VIDEO_TEASER_FILE ) ) . '/languages' );
    }

    /**
     * Plugin activation callback.
     */
    public static function activate() {
        require_once VIDEO_TEASER_PATH . 'includes/class-post-type.php';
        $post_type = new Video_Teaser_Post_Type();
        $post_type->register();
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation callback.
     */
    public static function deactivate() {
        flush_rewrite_rules();
    }
}
