<?php
/**
 * Plugin Name: Video Teaser
 * Plugin URI: https://github.com/breonwilliams/video-teaser
 * Description: Create engaging video teasers with autoplay loop and click-to-play functionality. Supports Media Library, YouTube, Vimeo, and external MP4 sources. Powered by Plyr.
 * Version: 1.0.0
 * Author: Breon Williams
 * Author URI: https://breonwilliams.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: video-teaser
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants.
define( 'VIDEO_TEASER_VERSION', '1.0.0' );
define( 'VIDEO_TEASER_FILE', __FILE__ );
define( 'VIDEO_TEASER_PATH', plugin_dir_path( __FILE__ ) );
define( 'VIDEO_TEASER_URL', plugin_dir_url( __FILE__ ) );

// Load main class and boot.
require_once VIDEO_TEASER_PATH . 'includes/class-video-teaser.php';

// Activation / deactivation.
register_activation_hook( __FILE__, array( 'Video_Teaser', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Video_Teaser', 'deactivate' ) );

// Initialize.
Video_Teaser::instance();
