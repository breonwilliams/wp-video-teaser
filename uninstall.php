<?php
/**
 * Uninstall script for Video Teaser plugin
 * 
 * This file is executed when the plugin is deleted from WordPress admin.
 * It cleans up all plugin data from the database.
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all video_teaser posts and their meta data
$video_teasers = get_posts(array(
    'post_type' => 'video_teaser',
    'numberposts' => -1,
    'post_status' => 'any'
));

foreach ($video_teasers as $teaser) {
    // Delete all meta data for this post
    delete_post_meta($teaser->ID, '_youtube_url');
    delete_post_meta($teaser->ID, '_video_id');
    delete_post_meta($teaser->ID, '_start_time');
    delete_post_meta($teaser->ID, '_end_time');
    
    // Force delete the post (bypass trash)
    wp_delete_post($teaser->ID, true);
}

// Remove any orphaned meta data (safety cleanup)
global $wpdb;
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_youtube_url%' OR meta_key LIKE '_video_id%' OR meta_key LIKE '_start_time%' OR meta_key LIKE '_end_time%'");

// Clear any cached data
wp_cache_flush();