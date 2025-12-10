<?php
/**
 * Plugin Updater for Logo Collision Pro
 * 
 * Handles automatic updates from custom server with license validation
 * 
 * @package Logo_Collision_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CAA_Plugin_Updater
 * 
 * Custom WordPress plugin updater that checks exzent.de for updates
 */
class CAA_Plugin_Updater {
    
    /**
     * Update server URL
     */
    const UPDATE_SERVER = 'https://exzent.de';
    
    /**
     * Plugin slug
     */
    private $plugin_slug = 'logo-collision-pro';
    
    /**
     * Plugin basename
     */
    private $plugin_basename;
    
    /**
     * Plugin version
     */
    private $plugin_version;
    
    /**
     * Cache key for update data
     */
    private $cache_key = 'caa_pro_update_data';
    
    /**
     * Cache expiration (12 hours)
     */
    private $cache_expiration = 43200;
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->plugin_basename = 'logo-collision-pro/LogoCollision.php';
        $this->plugin_version = defined('CAA_VERSION') ? CAA_VERSION : '1.0.0';
        
        // Hook into WordPress update system
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_info'), 10, 3);
        add_action('in_plugin_update_message-' . $this->plugin_basename, array($this, 'update_message'), 10, 2);
        
        // Clear cache on license status change
        add_action('update_option_caa_pro_license', array($this, 'clear_update_cache'));
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Check if license is active
        $license_manager = CAA_License_Manager::get_instance();
        if (!$license_manager->is_license_active()) {
            return $transient;
        }
        
        // Get remote version info
        $remote_data = $this->get_remote_version_info();
        
        if ($remote_data && isset($remote_data['version'])) {
            if (version_compare($this->plugin_version, $remote_data['version'], '<')) {
                $transient->response[$this->plugin_basename] = (object) array(
                    'slug'        => $this->plugin_slug,
                    'plugin'      => $this->plugin_basename,
                    'new_version' => $remote_data['version'],
                    'url'         => isset($remote_data['homepage']) ? $remote_data['homepage'] : '',
                    'package'     => isset($remote_data['download_url']) ? $this->get_download_url($remote_data['download_url']) : '',
                    'icons'       => isset($remote_data['icons']) ? $remote_data['icons'] : array(),
                    'banners'     => isset($remote_data['banners']) ? $remote_data['banners'] : array(),
                    'requires'    => isset($remote_data['requires']) ? $remote_data['requires'] : '5.0',
                    'tested'      => isset($remote_data['tested']) ? $remote_data['tested'] : '',
                    'requires_php'=> isset($remote_data['requires_php']) ? $remote_data['requires_php'] : '7.4'
                );
            }
        }
        
        return $transient;
    }
    
    /**
     * Get plugin info for WordPress plugins API
     */
    public function plugin_info($result, $action, $args) {
        if ($action !== 'plugin_information') {
            return $result;
        }
        
        if (!isset($args->slug) || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $remote_data = $this->get_remote_version_info();
        
        if ($remote_data) {
            return (object) array(
                'name'          => isset($remote_data['name']) ? $remote_data['name'] : 'Logo Collision Pro',
                'slug'          => $this->plugin_slug,
                'version'       => isset($remote_data['version']) ? $remote_data['version'] : $this->plugin_version,
                'author'        => isset($remote_data['author']) ? $remote_data['author'] : 'wpmitch',
                'homepage'      => isset($remote_data['homepage']) ? $remote_data['homepage'] : 'https://exzent.de/wordpress-plugin/logo-collision',
                'requires'      => isset($remote_data['requires']) ? $remote_data['requires'] : '5.0',
                'tested'        => isset($remote_data['tested']) ? $remote_data['tested'] : '',
                'requires_php'  => isset($remote_data['requires_php']) ? $remote_data['requires_php'] : '7.4',
                'sections'      => isset($remote_data['sections']) ? $remote_data['sections'] : array(
                    'description' => 'Logo Collision Pro - Advanced scroll animations for your WordPress logo.',
                    'changelog'   => isset($remote_data['changelog']) ? $remote_data['changelog'] : ''
                ),
                'download_link' => isset($remote_data['download_url']) ? $this->get_download_url($remote_data['download_url']) : ''
            );
        }
        
        return $result;
    }
    
    /**
     * Get remote version info from update server
     */
    private function get_remote_version_info() {
        // Check cache first
        $cached = get_transient($this->cache_key);
        if ($cached !== false) {
            return $cached;
        }
        
        $license_manager = CAA_License_Manager::get_instance();
        $license_key = $license_manager->get_license_key();
        
        $response = wp_remote_get(
            self::UPDATE_SERVER . '/wp-json/logo-collision-pro/v1/update-check',
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json',
                    'X-License-Key' => $license_key
                )
            )
        );
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if ($data && isset($data['version'])) {
            set_transient($this->cache_key, $data, $this->cache_expiration);
            return $data;
        }
        
        return false;
    }
    
    /**
     * Get download URL with license key
     */
    private function get_download_url($base_url) {
        $license_manager = CAA_License_Manager::get_instance();
        $license_key = $license_manager->get_license_key();
        
        return add_query_arg(array(
            'license_key' => urlencode($license_key),
            'site_url'    => urlencode(home_url())
        ), $base_url);
    }
    
    /**
     * Show update message if license is not active
     */
    public function update_message($plugin_data, $response) {
        $license_manager = CAA_License_Manager::get_instance();
        
        if (!$license_manager->is_license_active()) {
            echo '<br><span style="color: #d63638;">';
            esc_html_e('Please activate your license to receive automatic updates.', 'logo-collision');
            echo ' <a href="' . esc_url(admin_url('options-general.php?page=logo-collision#pro-version')) . '">';
            esc_html_e('Enter License Key', 'logo-collision');
            echo '</a></span>';
        }
    }
    
    /**
     * Clear update cache
     */
    public function clear_update_cache() {
        delete_transient($this->cache_key);
        delete_site_transient('update_plugins');
    }
}

// Initialize
CAA_Plugin_Updater::get_instance();
