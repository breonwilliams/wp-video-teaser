<?php
/**
 * Uninstall script for Video Teaser plugin.
 *
 * Cleans up all plugin data when the plugin is deleted from WordPress admin.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete all video_teaser posts and their meta data.
$vt_posts = get_posts( array(
    'post_type'   => 'video_teaser',
    'numberposts' => -1,
    'post_status' => 'any',
) );

foreach ( $vt_posts as $vt_post ) {
    wp_delete_post( $vt_post->ID, true );
}

// Clean up any orphaned meta data (v2 keys).
global $wpdb;
$vt_like = $wpdb->esc_like( '_vt_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cleanup on uninstall requires direct query
$wpdb->query(
    $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s", $vt_like )
);

// Legacy v1 meta keys are not explicitly deleted here because
// wp_delete_post() above already removes all postmeta for video_teaser posts.
// Deleting generic keys like _start_time globally would risk removing
// other plugins' data.

wp_cache_flush();
