<?php
/**
 * Shortcode handler for frontend rendering.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Video_Teaser_Shortcode {

    /**
     * Track whether any shortcode has been rendered on this page.
     *
     * @var bool
     */
    private static $has_rendered = false;

    /**
     * Initialize hooks.
     */
    public function init() {
        add_action( 'init', array( $this, 'register' ) );
    }

    /**
     * Register the shortcode.
     */
    public function register() {
        add_shortcode( 'video_teaser', array( $this, 'render' ) );
    }

    /**
     * Check if any shortcode was rendered on this page load.
     *
     * @return bool
     */
    public static function has_rendered() {
        return self::$has_rendered;
    }

    /**
     * Render the shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output.
     */
    public function render( $atts ) {
        $atts = shortcode_atts( array(
            'id' => 0,
        ), $atts, 'video_teaser' );

        $post_id = absint( $atts['id'] );
        if ( $post_id <= 0 ) {
            return '<p>' . esc_html__( 'Invalid video teaser ID.', 'video-teaser' ) . '</p>';
        }

        $post = get_post( $post_id );
        if ( ! $post || $post->post_type !== Video_Teaser_Post_Type::SLUG ) {
            return '<p>' . esc_html__( 'Video teaser not found.', 'video-teaser' ) . '</p>';
        }

        $source = Video_Teaser_Video_Source::get_source_data( $post_id );

        if ( empty( $source['source_type'] ) ) {
            return '<p>' . esc_html__( 'Video teaser not configured.', 'video-teaser' ) . '</p>';
        }

        // Validate that the active source has usable data.
        if ( ! $this->source_is_ready( $source ) ) {
            return '<p>' . esc_html__( 'Video source is unavailable.', 'video-teaser' ) . '</p>';
        }

        // Enqueue assets on first render.
        if ( ! self::$has_rendered ) {
            self::$has_rendered = true;
            Video_Teaser_Assets::enqueue_frontend();
        }

        // Gather settings via single query.
        $all_meta           = get_post_meta( $post_id );
        $teaser_enabled     = isset( $all_meta['_vt_teaser_enabled'][0] ) ? $all_meta['_vt_teaser_enabled'][0] : '';
        $start_time         = isset( $all_meta['_vt_start_time'][0] ) ? $all_meta['_vt_start_time'][0] : '';
        $end_time           = isset( $all_meta['_vt_end_time'][0] ) ? $all_meta['_vt_end_time'][0] : '';
        $button_color       = ! empty( $all_meta['_vt_button_color'][0] ) ? $all_meta['_vt_button_color'][0] : '#00b3ff';
        $aspect_ratio       = ! empty( $all_meta['_vt_aspect_ratio'][0] ) ? $all_meta['_vt_aspect_ratio'][0] : '16:9';
        $poster_id          = isset( $all_meta['_vt_poster_image'][0] ) ? $all_meta['_vt_poster_image'][0] : '';
        $poster_url         = $poster_id ? wp_get_attachment_image_url( absint( $poster_id ), 'full' ) : '';

        // Validate aspect ratio.
        $allowed_ratios = array( '16:9', '4:3', '21:9', '1:1', '9:16', 'auto' );
        if ( ! in_array( $aspect_ratio, $allowed_ratios, true ) ) {
            $aspect_ratio = '16:9';
        }

        // Legacy fallbacks for teaser settings.
        if ( $teaser_enabled === '' ) {
            $legacy_start = isset( $all_meta['_start_time'][0] ) ? $all_meta['_start_time'][0] : '';
            if ( $legacy_start !== '' ) {
                $teaser_enabled = '1';
                $start_time     = $legacy_start;
                $end_time       = isset( $all_meta['_end_time'][0] ) ? $all_meta['_end_time'][0] : '';
            } else {
                $teaser_enabled = '1';
            }
        }
        if ( $button_color === '#00b3ff' ) {
            $legacy_btn = isset( $all_meta['_button_color'][0] ) ? $all_meta['_button_color'][0] : '';
            if ( ! empty( $legacy_btn ) ) {
                $button_color = $legacy_btn;
            }
        }
        $start_time = $start_time !== '' ? absint( $start_time ) : 0;
        $end_time   = $end_time !== '' ? absint( $end_time ) : 10;

        // Ensure valid time range.
        if ( $start_time >= $end_time ) {
            $start_time = 0;
            $end_time   = 10;
        }

        $unique_id = 'vt-' . $post_id . '-' . substr( uniqid(), -6 );

        $container_classes = array( 'vt-container' );

        $data_attrs = array(
            'data-vt-source'  => esc_attr( $source['source_type'] ),
            'data-vt-ratio'   => esc_attr( $aspect_ratio ),
            'data-vt-teaser'  => esc_attr( $teaser_enabled ),
            'data-vt-start'   => esc_attr( $start_time ),
            'data-vt-end'     => esc_attr( $end_time ),
        );

        // Add source-specific data attributes.
        switch ( $source['source_type'] ) {
            case Video_Teaser_Video_Source::TYPE_YOUTUBE:
                $data_attrs['data-vt-video-id'] = esc_attr( $source['youtube_id'] );
                break;

            case Video_Teaser_Video_Source::TYPE_VIMEO:
                $data_attrs['data-vt-video-id'] = esc_attr( $source['vimeo_id'] );
                break;

            case Video_Teaser_Video_Source::TYPE_MEDIA_LIBRARY:
                $data_attrs['data-vt-video-url'] = esc_url( $source['video_url'] );
                break;

            case Video_Teaser_Video_Source::TYPE_EXTERNAL:
                $data_attrs['data-vt-video-url'] = esc_url( $source['external_url'] );
                break;
        }

        $data_string = '';
        foreach ( $data_attrs as $key => $val ) {
            $data_string .= ' ' . $key . '="' . $val . '"';
        }

        // Convert aspect ratio from colon format (16:9) to CSS format (16/9).
        $aspect_ratio_css = '';
        if ( $aspect_ratio !== 'auto' ) {
            $aspect_ratio_css = '--vt-aspect-ratio: ' . str_replace( ':', '/', $aspect_ratio ) . ';';
        }

        ob_start();
        ?>
        <style>
        #<?php echo esc_attr( $unique_id ); ?> {
            --vt-button-color: <?php echo sanitize_hex_color( $button_color ) ? esc_attr( $button_color ) : '#00b3ff'; ?>;
        }
        <?php if ( $aspect_ratio_css ) : ?>
        #<?php echo esc_attr( $unique_id ); ?> .vt-player-wrap {
            <?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value constructed from validated whitelist ratios only
            echo $aspect_ratio_css;
            ?>
        }
        <?php endif; ?>
        </style>
        <div class="<?php echo esc_attr( implode( ' ', $container_classes ) ); ?>" id="<?php echo esc_attr( $unique_id ); ?>"<?php
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- all values pre-escaped with esc_attr() or esc_url()
            echo $data_string;
        ?>>
            <div class="vt-player-wrap">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render_player_element returns escaped HTML
                echo $this->render_player_element( $source, $poster_url );
                ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Check if the resolved source has enough data to render.
     *
     * @param array $source Source data.
     * @return bool
     */
    private function source_is_ready( $source ) {
        switch ( $source['source_type'] ) {
            case Video_Teaser_Video_Source::TYPE_YOUTUBE:
                return ! empty( $source['youtube_id'] );

            case Video_Teaser_Video_Source::TYPE_VIMEO:
                return ! empty( $source['vimeo_id'] );

            case Video_Teaser_Video_Source::TYPE_MEDIA_LIBRARY:
                return ! empty( $source['video_url'] );

            case Video_Teaser_Video_Source::TYPE_EXTERNAL:
                return ! empty( $source['external_url'] );
        }
        return false;
    }

    /**
     * Render the inner player element based on source type.
     *
     * @param array  $source     Source data.
     * @param string $poster_url Optional poster image URL.
     * @return string HTML.
     */
    private function render_player_element( $source, $poster_url = '' ) {
        switch ( $source['source_type'] ) {
            case Video_Teaser_Video_Source::TYPE_YOUTUBE:
                return sprintf(
                    '<div class="vt-player" data-plyr-provider="youtube" data-plyr-embed-id="%s"></div>',
                    esc_attr( $source['youtube_id'] )
                );

            case Video_Teaser_Video_Source::TYPE_VIMEO:
                return sprintf(
                    '<div class="vt-player" data-plyr-provider="vimeo" data-plyr-embed-id="%s"></div>',
                    esc_attr( $source['vimeo_id'] )
                );

            case Video_Teaser_Video_Source::TYPE_MEDIA_LIBRARY:
                return $this->render_video_tag( $source['video_url'], $poster_url );

            case Video_Teaser_Video_Source::TYPE_EXTERNAL:
                return $this->render_video_tag( $source['external_url'], $poster_url );
        }
        return '';
    }

    /**
     * Build a <video> tag with optional poster and appropriate preload.
     *
     * @param string $video_url  Video source URL.
     * @param string $poster_url Poster image URL (empty to use preload="auto" fallback).
     * @return string HTML.
     */
    private function render_video_tag( $video_url, $poster_url = '' ) {
        $poster_attr  = $poster_url ? sprintf( ' poster="%s"', esc_url( $poster_url ) ) : '';
        $preload      = $poster_url ? 'metadata' : 'auto';

        return sprintf(
            '<video class="vt-player" playsinline preload="%s"%s><source src="%s" type="video/mp4"></video>',
            esc_attr( $preload ),
            $poster_attr,
            esc_url( $video_url )
        );
    }
}
