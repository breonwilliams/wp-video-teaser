<?php
/**
 * Admin meta boxes for the Video Teaser CPT.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Video_Teaser_Meta_Boxes {

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'add_meta_boxes', array( $this, 'register' ) );
        add_action( 'save_post', array( $this, 'save' ) );
        add_action( 'edit_form_after_title', array( $this, 'render_preview' ) );
    }

    /**
     * Register meta boxes.
     */
    public function register() {
        add_meta_box(
            'vt_video_source',
            __( 'Video Source', 'video-teaser' ),
            array( $this, 'render_source_box' ),
            Video_Teaser_Post_Type::SLUG,
            'side',
            'high'
        );

        add_meta_box(
            'vt_teaser_settings',
            __( 'Teaser Settings', 'video-teaser' ),
            array( $this, 'render_teaser_box' ),
            Video_Teaser_Post_Type::SLUG,
            'side',
            'high'
        );

        add_meta_box(
            'vt_player_appearance',
            __( 'Player Appearance', 'video-teaser' ),
            array( $this, 'render_appearance_box' ),
            Video_Teaser_Post_Type::SLUG,
            'side',
            'default'
        );

        add_meta_box(
            'vt_shortcode',
            __( 'Shortcode', 'video-teaser' ),
            array( $this, 'render_shortcode_box' ),
            Video_Teaser_Post_Type::SLUG,
            'side',
            'high'
        );
    }

    /**
     * Render the video preview directly after the title (no postbox wrapper).
     *
     * @param WP_Post $post Current post.
     */
    public function render_preview( $post ) {
        if ( get_post_type( $post ) !== Video_Teaser_Post_Type::SLUG ) {
            return;
        }
        ?>
        <div id="vt-admin-preview">
            <div class="vt-preview-empty">
                <span class="dashicons dashicons-video-alt3"></span>
                <p><?php esc_html_e( 'Enter a video URL to see preview', 'video-teaser' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Video Source meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_source_box( $post ) {
        wp_nonce_field( 'vt_save_meta', 'vt_nonce' );

        $source = Video_Teaser_Video_Source::get_source_data( $post->ID );
        $type   = $source['source_type'];

        // For legacy posts, read old meta keys for display.
        $youtube_url  = ! empty( $source['youtube_url'] ) ? $source['youtube_url'] : '';
        $vimeo_url    = ! empty( $source['vimeo_url'] ) ? $source['vimeo_url'] : '';
        $external_url = ! empty( $source['external_url'] ) ? $source['external_url'] : '';
        $media_id     = ! empty( $source['media_id'] ) ? $source['media_id'] : 0;
        $media_url    = $media_id ? wp_get_attachment_url( $media_id ) : '';
        $media_title  = $media_id ? get_the_title( $media_id ) : '';
        ?>
        <div class="vt-source-tabs">
            <div class="vt-source-radios">
                <?php
                $sources = array(
                    'media_library' => __( 'Media Library', 'video-teaser' ),
                    'youtube'       => __( 'YouTube', 'video-teaser' ),
                    'vimeo'         => __( 'Vimeo', 'video-teaser' ),
                    'external'      => __( 'External URL', 'video-teaser' ),
                );
                $selected = $type ?: 'youtube';
                foreach ( $sources as $value => $label ) : ?>
                    <label class="vt-source-radio">
                        <input type="radio" name="vt_source_type_radio" value="<?php echo esc_attr( $value ); ?>" <?php checked( $selected, $value ); ?> />
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="vt_source_type" id="vt_source_type" value="<?php echo esc_attr( $type ?: 'youtube' ); ?>" />

            <!-- Media Library Panel -->
            <div class="vt-tab-panel<?php echo ( $type === 'media_library' ) ? ' vt-tab-panel--active' : ''; ?>" data-panel="media_library" role="tabpanel">
                <div class="vt-field">
                    <div class="vt-media-preview" id="vt-media-preview" style="<?php echo $media_id ? '' : 'display:none;'; ?>">
                        <div class="vt-media-info">
                            <span class="dashicons dashicons-media-video"></span>
                            <span class="vt-media-title" id="vt-media-title"><?php echo esc_html( $media_title ); ?></span>
                            <button type="button" class="vt-media-remove" id="vt-media-remove" title="<?php esc_attr_e( 'Remove video', 'video-teaser' ); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="button button-secondary" id="vt-media-select">
                        <span class="dashicons dashicons-upload" style="vertical-align: middle; margin-top: -2px;"></span>
                        <span class="vt-media-btn-label"><?php echo $media_id ? esc_html__( 'Change Video', 'video-teaser' ) : esc_html__( 'Select Video', 'video-teaser' ); ?></span>
                    </button>
                    <input type="hidden" name="vt_media_id" id="vt_media_id" value="<?php echo esc_attr( $media_id ); ?>" />
                    <p class="description"><?php esc_html_e( 'Choose a video from your Media Library or upload a new one.', 'video-teaser' ); ?></p>
                </div>
            </div>

            <!-- YouTube Panel -->
            <div class="vt-tab-panel<?php echo ( $type === 'youtube' || empty( $type ) ) ? ' vt-tab-panel--active' : ''; ?>" data-panel="youtube" role="tabpanel">
                <div class="vt-field">
                    <label for="vt_youtube_url"><?php esc_html_e( 'YouTube URL', 'video-teaser' ); ?></label>
                    <input type="url" id="vt_youtube_url" name="vt_youtube_url" value="<?php echo esc_attr( $youtube_url ); ?>" class="widefat" placeholder="https://www.youtube.com/watch?v=VIDEO_ID" />
                    <p class="description"><?php esc_html_e( 'Paste the full YouTube video URL.', 'video-teaser' ); ?></p>
                </div>
            </div>

            <!-- Vimeo Panel -->
            <div class="vt-tab-panel<?php echo ( $type === 'vimeo' ) ? ' vt-tab-panel--active' : ''; ?>" data-panel="vimeo" role="tabpanel">
                <div class="vt-field">
                    <label for="vt_vimeo_url"><?php esc_html_e( 'Vimeo URL', 'video-teaser' ); ?></label>
                    <input type="url" id="vt_vimeo_url" name="vt_vimeo_url" value="<?php echo esc_attr( $vimeo_url ); ?>" class="widefat" placeholder="https://vimeo.com/VIDEO_ID" />
                    <p class="description"><?php esc_html_e( 'Paste the full Vimeo video URL.', 'video-teaser' ); ?></p>
                </div>
            </div>

            <!-- External URL Panel -->
            <div class="vt-tab-panel<?php echo ( $type === 'external' ) ? ' vt-tab-panel--active' : ''; ?>" data-panel="external" role="tabpanel">
                <div class="vt-field">
                    <label for="vt_external_url"><?php esc_html_e( 'Video URL', 'video-teaser' ); ?></label>
                    <input type="url" id="vt_external_url" name="vt_external_url" value="<?php echo esc_attr( $external_url ); ?>" class="widefat" placeholder="https://example.com/video.mp4" />
                    <p class="description"><?php esc_html_e( 'Direct link to an MP4 video file.', 'video-teaser' ); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Teaser Settings meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_teaser_box( $post ) {
        $teaser_enabled = get_post_meta( $post->ID, '_vt_teaser_enabled', true );
        $start_time     = get_post_meta( $post->ID, '_vt_start_time', true );
        $end_time       = get_post_meta( $post->ID, '_vt_end_time', true );

        // Legacy fallback.
        if ( $teaser_enabled === '' ) {
            $legacy_start = get_post_meta( $post->ID, '_start_time', true );
            if ( $legacy_start !== '' ) {
                $teaser_enabled = '1';
                $start_time     = $legacy_start;
                $end_time       = get_post_meta( $post->ID, '_end_time', true );
            } else {
                $teaser_enabled = '1';
            }
        }

        $start_time = $start_time !== '' ? $start_time : 0;
        $end_time   = $end_time !== '' ? $end_time : 10;
        ?>
        <div class="vt-teaser-settings">
            <div class="vt-field">
                <label>
                    <input type="checkbox" id="vt_teaser_enabled" name="vt_teaser_enabled" value="1" <?php checked( $teaser_enabled, '1' ); ?> />
                    <?php esc_html_e( 'Enable teaser preview', 'video-teaser' ); ?>
                </label>
            </div>

            <div class="vt-teaser-fields" id="vt-teaser-fields" style="<?php echo $teaser_enabled !== '1' ? 'display:none;' : ''; ?>">
                <div class="vt-field-row">
                    <div class="vt-field vt-field--half">
                        <label for="vt_start_time"><?php esc_html_e( 'Start Time (seconds)', 'video-teaser' ); ?></label>
                        <input type="number" id="vt_start_time" name="vt_start_time" value="<?php echo esc_attr( $start_time ); ?>" min="0" step="1" />
                    </div>
                    <div class="vt-field vt-field--half">
                        <label for="vt_end_time"><?php esc_html_e( 'End Time (seconds)', 'video-teaser' ); ?></label>
                        <input type="number" id="vt_end_time" name="vt_end_time" value="<?php echo esc_attr( $end_time ); ?>" min="1" max="7200" step="1" />
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Player Appearance meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_appearance_box( $post ) {
        $button_color = get_post_meta( $post->ID, '_vt_button_color', true );

        // Legacy fallback.
        if ( $button_color === '' ) {
            $button_color = get_post_meta( $post->ID, '_button_color', true );
        }

        $button_color = $button_color ?: '#00b3ff';

        $aspect_ratio = get_post_meta( $post->ID, '_vt_aspect_ratio', true );
        $aspect_ratio = $aspect_ratio ?: '16:9';
        ?>
        <div class="vt-appearance-settings">
            <div class="vt-field">
                <label for="vt_aspect_ratio"><?php esc_html_e( 'Aspect Ratio', 'video-teaser' ); ?></label>
                <select id="vt_aspect_ratio" name="vt_aspect_ratio" class="widefat">
                    <option value="16:9" <?php selected( $aspect_ratio, '16:9' ); ?>>16:9</option>
                    <option value="4:3" <?php selected( $aspect_ratio, '4:3' ); ?>>4:3</option>
                    <option value="21:9" <?php selected( $aspect_ratio, '21:9' ); ?>>21:9</option>
                    <option value="auto" <?php selected( $aspect_ratio, 'auto' ); ?>><?php esc_html_e( 'Auto (match video)', 'video-teaser' ); ?></option>
                </select>
            </div>
            <div class="vt-field">
                <label for="vt_button_color"><?php esc_html_e( 'Player Color', 'video-teaser' ); ?></label>
                <input type="text" id="vt_button_color" name="vt_button_color" value="<?php echo esc_attr( $button_color ); ?>" class="vt-color-picker" data-default-color="#00b3ff" />
                <p class="description"><?php esc_html_e( 'Play button and progress bar color.', 'video-teaser' ); ?></p>
            </div>
        </div>
        <?php
    }

    /**
     * Render the Shortcode meta box.
     *
     * @param WP_Post $post Current post.
     */
    public function render_shortcode_box( $post ) {
        ?>
        <div class="vt-shortcode-box">
            <p><?php esc_html_e( 'Copy this shortcode and paste it into any page or post:', 'video-teaser' ); ?></p>
            <div class="vt-shortcode-field">
                <input type="text" value='[video_teaser id="<?php echo esc_attr( $post->ID ); ?>"]' readonly id="vt-shortcode-input" />
                <button type="button" class="button" id="vt-shortcode-copy" title="<?php esc_attr_e( 'Copy to clipboard', 'video-teaser' ); ?>">
                    <?php esc_html_e( 'Copy', 'video-teaser' ); ?>
                </button>
            </div>
            <p class="vt-copy-feedback" id="vt-copy-feedback" style="display:none;"><?php esc_html_e( 'Copied!', 'video-teaser' ); ?></p>
        </div>
        <?php
    }

    /**
     * Save meta data.
     *
     * @param int $post_id Post ID.
     */
    public function save( $post_id ) {
        if ( get_post_type( $post_id ) !== Video_Teaser_Post_Type::SLUG ) {
            return;
        }

        if ( ! isset( $_POST['vt_nonce'] ) || ! wp_verify_nonce( $_POST['vt_nonce'], 'vt_save_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Source type.
        $source_type = isset( $_POST['vt_source_type'] ) ? sanitize_text_field( $_POST['vt_source_type'] ) : '';
        if ( ! Video_Teaser_Video_Source::is_valid_type( $source_type ) ) {
            $source_type = 'youtube';
        }
        update_post_meta( $post_id, '_vt_source_type', $source_type );

        // Media Library.
        $media_id = isset( $_POST['vt_media_id'] ) ? absint( $_POST['vt_media_id'] ) : 0;
        update_post_meta( $post_id, '_vt_media_id', $media_id );

        // YouTube.
        $youtube_url = isset( $_POST['vt_youtube_url'] ) ? sanitize_url( $_POST['vt_youtube_url'] ) : '';
        $youtube_id  = '';
        if ( ! empty( $youtube_url ) && Video_Teaser_Video_Source::validate_youtube_url( $youtube_url ) ) {
            $youtube_id = Video_Teaser_Video_Source::extract_youtube_id( $youtube_url );
        }
        update_post_meta( $post_id, '_vt_youtube_url', $youtube_url );
        update_post_meta( $post_id, '_vt_youtube_id', $youtube_id );

        // Vimeo.
        $vimeo_url = isset( $_POST['vt_vimeo_url'] ) ? sanitize_url( $_POST['vt_vimeo_url'] ) : '';
        $vimeo_id  = '';
        if ( ! empty( $vimeo_url ) && Video_Teaser_Video_Source::validate_vimeo_url( $vimeo_url ) ) {
            $vimeo_id = Video_Teaser_Video_Source::extract_vimeo_id( $vimeo_url );
        }
        update_post_meta( $post_id, '_vt_vimeo_url', $vimeo_url );
        update_post_meta( $post_id, '_vt_vimeo_id', $vimeo_id );

        // External URL.
        $external_url = isset( $_POST['vt_external_url'] ) ? sanitize_url( $_POST['vt_external_url'] ) : '';
        update_post_meta( $post_id, '_vt_external_url', $external_url );

        // Teaser settings.
        $teaser_enabled = isset( $_POST['vt_teaser_enabled'] ) ? '1' : '0';
        update_post_meta( $post_id, '_vt_teaser_enabled', $teaser_enabled );

        $start_time = isset( $_POST['vt_start_time'] ) ? absint( $_POST['vt_start_time'] ) : 0;
        $end_time   = isset( $_POST['vt_end_time'] ) ? absint( $_POST['vt_end_time'] ) : 10;

        if ( $end_time <= $start_time ) {
            $end_time = $start_time + 10;
        }
        if ( $end_time > 7200 ) {
            $end_time = 7200;
        }

        update_post_meta( $post_id, '_vt_start_time', $start_time );
        update_post_meta( $post_id, '_vt_end_time', $end_time );

        // Appearance.
        $aspect_ratio = isset( $_POST['vt_aspect_ratio'] ) ? sanitize_text_field( $_POST['vt_aspect_ratio'] ) : '16:9';
        if ( ! in_array( $aspect_ratio, array( '16:9', '4:3', '21:9', 'auto' ), true ) ) {
            $aspect_ratio = '16:9';
        }
        update_post_meta( $post_id, '_vt_aspect_ratio', $aspect_ratio );

        $button_color = isset( $_POST['vt_button_color'] ) ? sanitize_hex_color( $_POST['vt_button_color'] ) : '#00b3ff';
        update_post_meta( $post_id, '_vt_button_color', $button_color ?: '#00b3ff' );

    }
}
