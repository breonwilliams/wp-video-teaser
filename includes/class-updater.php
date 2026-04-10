<?php
/**
 * GitHub-based auto-updater.
 *
 * @package Video_Teaser
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Video_Teaser_Updater {

    private $github_user = 'breonwilliams';
    private $github_repo = 'wp-video-teaser';
    private $plugin_slug;
    private $plugin_basename;
    private $current_version;
    private $transient_key = 'video_teaser_github_release';

    public function __construct() {
        $this->current_version = VIDEO_TEASER_VERSION;
        $this->plugin_basename = plugin_basename( VIDEO_TEASER_FILE );
        $this->plugin_slug     = dirname( $this->plugin_basename );
    }

    public function init() {
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );
        add_filter( 'plugins_api', array( $this, 'get_plugin_info' ), 10, 3 );
        add_filter( 'upgrader_post_install', array( $this, 'post_install' ), 10, 3 );
        add_action( 'admin_init', array( $this, 'maybe_clear_cache' ) );
    }

    /**
     * Clear update cache when requested via URL parameter.
     * Usage: /wp-admin/?clear_video_teaser_cache=1
     */
    public function maybe_clear_cache() {
        if ( isset( $_GET['clear_video_teaser_cache'] ) && current_user_can( 'manage_options' ) ) {
            delete_transient( $this->transient_key );
            delete_site_transient( 'update_plugins' );
            add_action( 'admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Video Teaser update cache cleared. Refresh the Plugins page to check for updates.</p></div>';
            });
        }
    }

    private function get_latest_release() {
        $cached = get_transient( $this->transient_key );
        if ( false !== $cached ) {
            return $cached;
        }

        $url      = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->github_user, $this->github_repo );
        $response = wp_remote_get( $url, array(
            'headers' => array(
                'Accept' => 'application/vnd.github.v3+json',
            ),
        ) );

        if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
            return false;
        }

        $release = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( empty( $release['tag_name'] ) ) {
            return false;
        }

        set_transient( $this->transient_key, $release, 12 * HOUR_IN_SECONDS );

        return $release;
    }

    private function get_remote_version( $release ) {
        return ltrim( $release['tag_name'], 'v' );
    }

    private function get_download_url( $release ) {
        // Prefer an attached ZIP asset.
        if ( ! empty( $release['assets'] ) ) {
            foreach ( $release['assets'] as $asset ) {
                if ( 'application/zip' === $asset['content_type'] || str_ends_with( $asset['name'], '.zip' ) ) {
                    return $asset['browser_download_url'];
                }
            }
        }

        // Fallback to GitHub auto-generated zipball.
        return $release['zipball_url'] ?? '';
    }

    public function check_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $transient;
        }

        $remote_version = $this->get_remote_version( $release );
        $download_url   = $this->get_download_url( $release );

        if ( version_compare( $remote_version, $this->current_version, '>' ) && $download_url ) {
            $transient->response[ $this->plugin_basename ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $remote_version,
                'url'         => $release['html_url'] ?? '',
                'package'     => $download_url,
                'icons'       => array(
                    '1x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-128x128.png',
                    '2x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-256x256.png',
                ),
            );
            // Remove from no_update if present.
            unset( $transient->no_update[ $this->plugin_basename ] );
        } else {
            // No update available - must add to no_update to clear stale notifications.
            unset( $transient->response[ $this->plugin_basename ] );
            $transient->no_update[ $this->plugin_basename ] = (object) array(
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_basename,
                'new_version' => $this->current_version,
                'url'         => $release['html_url'] ?? '',
                'package'     => '',
                'icons'       => array(
                    '1x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-128x128.png',
                    '2x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-256x256.png',
                ),
            );
        }

        return $transient;
    }

    public function get_plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== $this->plugin_slug ) {
            return $result;
        }

        $release = $this->get_latest_release();
        if ( ! $release ) {
            return $result;
        }

        $remote_version = $this->get_remote_version( $release );

        return (object) array(
            'name'          => 'Video Teaser',
            'slug'          => $this->plugin_slug,
            'version'       => $remote_version,
            'author'        => '<a href="https://breonwilliams.com">Breon Williams</a>',
            'homepage'      => 'https://github.com/' . $this->github_user . '/' . $this->github_repo,
            'download_link' => $this->get_download_url( $release ),
            'icons'         => array(
                '1x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-128x128.png',
                '2x' => 'https://raw.githubusercontent.com/' . $this->github_user . '/' . $this->github_repo . '/main/assets/icon-256x256.png',
            ),
            'sections'      => array(
                'description' => 'Create engaging video teasers with autoplay loop and click-to-play functionality.',
                'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
            ),
            'requires'      => '5.0',
            'tested'        => '6.7',
            'requires_php'  => '7.4',
        );
    }

    public function post_install( $response, $hook_extra, $result ) {
        if ( ! isset( $hook_extra['plugin'] ) || $hook_extra['plugin'] !== $this->plugin_basename ) {
            return $response;
        }

        global $wp_filesystem;

        $plugin_dir   = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
        $installed_to = $result['destination'];

        if ( $installed_to !== $plugin_dir ) {
            $wp_filesystem->move( $installed_to, $plugin_dir );
            $result['destination'] = $plugin_dir;
        }

        activate_plugin( $this->plugin_basename );

        // Clear cached data to force fresh update check.
        delete_transient( $this->transient_key );
        delete_site_transient( 'update_plugins' );

        return $response;
    }
}
