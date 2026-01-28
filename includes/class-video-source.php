<?php
/**
 * Video source detection, validation, and normalization.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Video_Teaser_Video_Source {

    const TYPE_MEDIA_LIBRARY = 'media_library';
    const TYPE_YOUTUBE       = 'youtube';
    const TYPE_VIMEO         = 'vimeo';
    const TYPE_EXTERNAL      = 'external';

    /**
     * Valid source types.
     *
     * @var array
     */
    private static $valid_types = array(
        self::TYPE_MEDIA_LIBRARY,
        self::TYPE_YOUTUBE,
        self::TYPE_VIMEO,
        self::TYPE_EXTERNAL,
    );

    /**
     * Check if a source type is valid.
     *
     * @param string $type Source type.
     * @return bool
     */
    public static function is_valid_type( $type ) {
        return in_array( $type, self::$valid_types, true );
    }

    /**
     * Validate a YouTube URL.
     *
     * @param string $url URL to validate.
     * @return bool
     */
    public static function validate_youtube_url( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        $parsed = wp_parse_url( $url );
        $valid_hosts = array( 'www.youtube.com', 'youtube.com', 'youtu.be', 'm.youtube.com' );

        if ( ! isset( $parsed['host'] ) || ! in_array( $parsed['host'], $valid_hosts, true ) ) {
            return false;
        }

        return self::extract_youtube_id( $url ) !== false;
    }

    /**
     * Extract YouTube video ID from URL.
     *
     * @param string $url YouTube URL.
     * @return string|false Video ID or false.
     */
    public static function extract_youtube_id( $url ) {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/';
        if ( preg_match( $pattern, $url, $matches ) ) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Validate a Vimeo URL.
     *
     * @param string $url URL to validate.
     * @return bool
     */
    public static function validate_vimeo_url( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        $parsed = wp_parse_url( $url );
        $valid_hosts = array( 'vimeo.com', 'www.vimeo.com', 'player.vimeo.com' );

        if ( ! isset( $parsed['host'] ) || ! in_array( $parsed['host'], $valid_hosts, true ) ) {
            return false;
        }

        return self::extract_vimeo_id( $url ) !== false;
    }

    /**
     * Extract Vimeo video ID from URL.
     *
     * @param string $url Vimeo URL.
     * @return string|false Video ID or false.
     */
    public static function extract_vimeo_id( $url ) {
        $pattern = '/(?:vimeo\.com\/(?:video\/|channels\/[^\/]+\/|groups\/[^\/]+\/videos\/|album\/\d+\/video\/)?|player\.vimeo\.com\/video\/)(\d+)/';
        if ( preg_match( $pattern, $url, $matches ) ) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Validate an external MP4 URL.
     *
     * @param string $url URL to validate.
     * @return bool
     */
    public static function validate_external_url( $url ) {
        if ( empty( $url ) ) {
            return false;
        }

        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return false;
        }

        $scheme = wp_parse_url( $url, PHP_URL_SCHEME );
        if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
            return false;
        }

        $path = wp_parse_url( $url, PHP_URL_PATH );

        return $path && preg_match( '/\.mp4(\?.*)?$/i', $path );
    }

    /**
     * Validate a media library attachment ID.
     *
     * @param int $attachment_id Attachment ID.
     * @return bool
     */
    public static function validate_media_attachment( $attachment_id ) {
        $attachment_id = absint( $attachment_id );
        if ( $attachment_id <= 0 ) {
            return false;
        }

        $post = get_post( $attachment_id );
        if ( ! $post || $post->post_type !== 'attachment' ) {
            return false;
        }

        $mime = get_post_mime_type( $attachment_id );
        return strpos( $mime, 'video/' ) === 0;
    }

    /**
     * Get the video URL for a media library attachment.
     *
     * @param int $attachment_id Attachment ID.
     * @return string|false URL or false.
     */
    public static function get_media_url( $attachment_id ) {
        if ( ! self::validate_media_attachment( $attachment_id ) ) {
            return false;
        }
        return wp_get_attachment_url( $attachment_id );
    }

    /**
     * Resolve source data for a given post, including legacy v1 fallback.
     *
     * @param int $post_id Post ID.
     * @return array Resolved source data.
     */
    public static function get_source_data( $post_id ) {
        $source_type = get_post_meta( $post_id, '_vt_source_type', true );

        // Legacy v1 fallback: no _vt_source_type means old YouTube-only data.
        if ( empty( $source_type ) ) {
            $legacy_url = get_post_meta( $post_id, '_youtube_url', true );
            $legacy_id  = get_post_meta( $post_id, '_video_id', true );

            if ( ! empty( $legacy_url ) && ! empty( $legacy_id ) ) {
                return array(
                    'source_type'  => self::TYPE_YOUTUBE,
                    'youtube_url'  => $legacy_url,
                    'youtube_id'   => $legacy_id,
                    'video_url'    => '',
                    'media_id'     => 0,
                    'vimeo_url'    => '',
                    'vimeo_id'     => '',
                    'external_url' => '',
                    'is_legacy'    => true,
                );
            }

            return array(
                'source_type'  => '',
                'youtube_url'  => '',
                'youtube_id'   => '',
                'video_url'    => '',
                'media_id'     => 0,
                'vimeo_url'    => '',
                'vimeo_id'     => '',
                'external_url' => '',
                'is_legacy'    => false,
            );
        }

        $data = array(
            'source_type'  => $source_type,
            'youtube_url'  => get_post_meta( $post_id, '_vt_youtube_url', true ),
            'youtube_id'   => get_post_meta( $post_id, '_vt_youtube_id', true ),
            'vimeo_url'    => get_post_meta( $post_id, '_vt_vimeo_url', true ),
            'vimeo_id'     => get_post_meta( $post_id, '_vt_vimeo_id', true ),
            'media_id'     => absint( get_post_meta( $post_id, '_vt_media_id', true ) ),
            'external_url' => get_post_meta( $post_id, '_vt_external_url', true ),
            'is_legacy'    => false,
        );

        // Resolve the playable URL for media library type.
        if ( $source_type === self::TYPE_MEDIA_LIBRARY && $data['media_id'] > 0 ) {
            $data['video_url'] = self::get_media_url( $data['media_id'] );
        } else {
            $data['video_url'] = '';
        }

        return $data;
    }
}
