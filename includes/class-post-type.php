<?php
/**
 * Custom Post Type registration.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Video_Teaser_Post_Type {

    /**
     * Post type slug.
     *
     * @var string
     */
    const SLUG = 'video_teaser';

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'init', array( $this, 'register' ) );
    }

    /**
     * Register the custom post type.
     */
    public function register() {
        $labels = array(
            'name'               => __( 'Video Teasers', 'video-teaser' ),
            'singular_name'      => __( 'Video Teaser', 'video-teaser' ),
            'menu_name'          => __( 'Video Teasers', 'video-teaser' ),
            'add_new'            => __( 'Add New', 'video-teaser' ),
            'add_new_item'       => __( 'Add New Video Teaser', 'video-teaser' ),
            'edit_item'          => __( 'Edit Video Teaser', 'video-teaser' ),
            'new_item'           => __( 'New Video Teaser', 'video-teaser' ),
            'view_item'          => __( 'View Video Teaser', 'video-teaser' ),
            'search_items'       => __( 'Search Video Teasers', 'video-teaser' ),
            'not_found'          => __( 'No video teasers found', 'video-teaser' ),
            'not_found_in_trash' => __( 'No video teasers found in Trash', 'video-teaser' ),
            'all_items'          => __( 'All Video Teasers', 'video-teaser' ),
        );

        $args = array(
            'labels'       => $labels,
            'public'       => false,
            'show_ui'      => true,
            'show_in_menu' => true,
            'menu_icon'    => 'dashicons-video-alt3',
            'supports'     => array( 'title' ),
            'has_archive'  => false,
            'rewrite'      => false,
            'capability_type' => 'post',
        );

        register_post_type( self::SLUG, $args );
    }
}
