<?php
/**
 * Plugin Name: Context-Aware Animation
 * Plugin URI: https://example.com/context-aware-animation
 * Description: Apply context-aware scroll animations to your WordPress header logo when it would collide with scrolling content.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: context-aware-animation
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CAA_VERSION', '1.0.0');
define('CAA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAA_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main plugin class
 */
class Context_Aware_Animation {
    
    /**
     * Instance of this class
     */
    private static $instance = null;
    
    /**
     * Get instance of this class
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('script_loader_tag', array($this, 'add_module_type'), 10, 3);
    }
    
    /**
     * Add type="module" to ES6 module scripts
     */
    public function add_module_type($tag, $handle, $src) {
        $module_handles = array('caa-utils', 'caa-text-splitter', 'caa-frontend');
        if (in_array($handle, $module_handles)) {
            $tag = '<script type="module" src="' . esc_url($src) . '"></script>';
        }
        return $tag;
    }
    
    /**
     * Add settings page to WordPress admin
     */
    public function add_settings_page() {
        add_options_page(
            __('Context-Aware Animation Settings', 'context-aware-animation'),
            __('Context-Aware Animation', 'context-aware-animation'),
            'manage_options',
            'context-aware-animation',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('caa_settings_group', 'caa_logo_id', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_selected_effect', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_effect'),
            'default' => '1'
        ));
        
        register_setting('caa_settings_group', 'caa_included_elements', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_excluded_elements', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_textarea_field',
            'default' => ''
        ));
        
        register_setting('caa_settings_group', 'caa_global_offset', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_debug_mode', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '0'
        ));
        
        // Global animation settings
        register_setting('caa_settings_group', 'caa_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.6'
        ));
        
        register_setting('caa_settings_group', 'caa_ease', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_ease'),
            'default' => 'power4'
        ));
        
        register_setting('caa_settings_group', 'caa_offset_start', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '30'
        ));
        
        register_setting('caa_settings_group', 'caa_offset_end', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '10'
        ));
        
        // Effect 1: Scale settings
        register_setting('caa_settings_group', 'caa_effect1_scale_down', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect1_origin_x', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect1_origin_y', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '50'
        ));
        
        // Effect 2: Blur settings
        register_setting('caa_settings_group', 'caa_effect2_blur_amount', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '5'
        ));
        
        register_setting('caa_settings_group', 'caa_effect2_blur_scale', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.9'
        ));
        
        register_setting('caa_settings_group', 'caa_effect2_blur_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.2'
        ));
        
        // Effect 4: Text Split settings
        register_setting('caa_settings_group', 'caa_effect4_text_x_range', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '50'
        ));
        
        register_setting('caa_settings_group', 'caa_effect4_text_y_range', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '40'
        ));
        
        register_setting('caa_settings_group', 'caa_effect4_stagger_amount', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        // Effect 5: Character Shuffle settings
        register_setting('caa_settings_group', 'caa_effect5_shuffle_iterations', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '2'
        ));
        
        register_setting('caa_settings_group', 'caa_effect5_shuffle_duration', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        register_setting('caa_settings_group', 'caa_effect5_char_delay', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_float'),
            'default' => '0.03'
        ));
        
        // Effect 6: Rotation settings
        register_setting('caa_settings_group', 'caa_effect6_rotation', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '-90'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_x_percent', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_offset'),
            'default' => '-5'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_origin_x', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '0'
        ));
        
        register_setting('caa_settings_group', 'caa_effect6_origin_y', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_percent'),
            'default' => '100'
        ));
    }
    
    /**
     * Sanitize effect selection
     */
    public function sanitize_effect($value) {
        $valid_effects = array('1', '2', '3', '4', '5', '6', '7');
        return in_array($value, $valid_effects) ? $value : '1';
    }
    
    /**
     * Sanitize offset value
     */
    public function sanitize_offset($value) {
        // Allow positive and negative integers
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        // Convert to integer and back to string to ensure it's a valid number
        $int_value = intval($value);
        return (string)$int_value;
    }
    
    /**
     * Sanitize float value
     */
    public function sanitize_float($value) {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        $float_value = floatval($value);
        return (string)$float_value;
    }
    
    /**
     * Sanitize percentage value (0-100)
     */
    public function sanitize_percent($value) {
        $value = trim($value);
        if ($value === '' || $value === null) {
            return '0';
        }
        $int_value = intval($value);
        // Clamp between 0 and 100
        $int_value = max(0, min(100, $int_value));
        return (string)$int_value;
    }
    
    /**
     * Sanitize easing type
     */
    public function sanitize_ease($value) {
        $valid_eases = array('power1', 'power2', 'power3', 'power4', 'expo', 'sine', 'back', 'elastic', 'bounce', 'none');
        return in_array($value, $valid_eases) ? $value : 'power4';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        require_once CAA_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on frontend
        if (is_admin()) {
            return;
        }
        
        // Get settings
        $logo_id = get_option('caa_logo_id', '');
        $selected_effect = get_option('caa_selected_effect', '1');
        $included_elements = get_option('caa_included_elements', '');
        $excluded_elements = get_option('caa_excluded_elements', '');
        $global_offset = get_option('caa_global_offset', '0');
        $debug_mode = get_option('caa_debug_mode', '0');
        
        // Get global animation settings
        $duration = get_option('caa_duration', '0.6');
        $ease = get_option('caa_ease', 'power4');
        $offset_start = get_option('caa_offset_start', '30');
        $offset_end = get_option('caa_offset_end', '10');
        
        // Don't enqueue if no logo ID is set
        if (empty($logo_id)) {
            return;
        }
        
        // Check if text splitting is needed (effects 4 and 5)
        $needs_text_splitting = in_array($selected_effect, array('4', '5'));
        
        // Enqueue GSAP from CDN
        wp_enqueue_script(
            'gsap',
            'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js',
            array(),
            '3.12.5',
            true
        );
        
        wp_enqueue_script(
            'gsap-scrolltrigger',
            'https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js',
            array('gsap'),
            '3.12.5',
            true
        );
        
        // Only enqueue SplitType and textSplitter for effects 4 and 5
        $frontend_dependencies = array('gsap', 'gsap-scrolltrigger');
        
        if ($needs_text_splitting) {
            // Enqueue SplitType from local assets - load in HEAD to ensure it's available before modules
            // ES modules execute after DOMContentLoaded, so head scripts will be ready
            wp_enqueue_script(
                'split-type',
                CAA_PLUGIN_URL . 'assets/js/splittype.js',
                array(),
                '0.3.4',
                false // Load in <head>, not footer
            );
            
            // Ensure SplitType script has no defer/async (some themes add these)
            add_filter('script_loader_tag', function($tag, $handle) {
                if ($handle === 'split-type') {
                    // Remove any defer or async attributes
                    $tag = preg_replace('/\s+(defer|async)(=[\'"][^\'"]*[\'"])?/i', '', $tag);
                }
                return $tag;
            }, 99, 2); // High priority to run after other filters
            
            // Enqueue utility scripts - ensure split-type loads first
            wp_enqueue_script(
                'caa-utils',
                CAA_PLUGIN_URL . 'assets/js/utils.js',
                array(),
                CAA_VERSION,
                true
            );
            
            wp_enqueue_script(
                'caa-text-splitter',
                CAA_PLUGIN_URL . 'assets/js/textSplitter.js',
                array('split-type', 'caa-utils'),
                CAA_VERSION,
                true
            );
            
            $frontend_dependencies[] = 'split-type';
            $frontend_dependencies[] = 'caa-text-splitter';
        }
        
        // Enqueue main frontend script
        wp_enqueue_script(
            'caa-frontend',
            CAA_PLUGIN_URL . 'assets/js/frontend.js',
            $frontend_dependencies,
            CAA_VERSION,
            true
        );
        
        // Build settings array with only selected effect's settings
        $settings_array = array(
            'logoId' => esc_js($logo_id),
            'selectedEffect' => esc_js($selected_effect),
            'includedElements' => esc_js($included_elements),
            'excludedElements' => esc_js($excluded_elements),
            'globalOffset' => esc_js($global_offset),
            'debugMode' => esc_js($debug_mode),
            // Global animation settings
            'duration' => esc_js($duration),
            'ease' => esc_js($ease),
            'offsetStart' => esc_js($offset_start),
            'offsetEnd' => esc_js($offset_end)
        );
        
        // Only add settings for the selected effect
        switch ($selected_effect) {
            case '1':
                $settings_array['effect1ScaleDown'] = esc_js(get_option('caa_effect1_scale_down', '0'));
                $settings_array['effect1OriginX'] = esc_js(get_option('caa_effect1_origin_x', '0'));
                $settings_array['effect1OriginY'] = esc_js(get_option('caa_effect1_origin_y', '50'));
                break;
            case '2':
                $settings_array['effect2BlurAmount'] = esc_js(get_option('caa_effect2_blur_amount', '5'));
                $settings_array['effect2BlurScale'] = esc_js(get_option('caa_effect2_blur_scale', '0.9'));
                $settings_array['effect2BlurDuration'] = esc_js(get_option('caa_effect2_blur_duration', '0.2'));
                break;
            case '4':
                $settings_array['effect4TextXRange'] = esc_js(get_option('caa_effect4_text_x_range', '50'));
                $settings_array['effect4TextYRange'] = esc_js(get_option('caa_effect4_text_y_range', '40'));
                $settings_array['effect4StaggerAmount'] = esc_js(get_option('caa_effect4_stagger_amount', '0.03'));
                break;
            case '5':
                $settings_array['effect5ShuffleIterations'] = esc_js(get_option('caa_effect5_shuffle_iterations', '2'));
                $settings_array['effect5ShuffleDuration'] = esc_js(get_option('caa_effect5_shuffle_duration', '0.03'));
                $settings_array['effect5CharDelay'] = esc_js(get_option('caa_effect5_char_delay', '0.03'));
                break;
            case '6':
                $settings_array['effect6Rotation'] = esc_js(get_option('caa_effect6_rotation', '-90'));
                $settings_array['effect6XPercent'] = esc_js(get_option('caa_effect6_x_percent', '-5'));
                $settings_array['effect6OriginX'] = esc_js(get_option('caa_effect6_origin_x', '0'));
                $settings_array['effect6OriginY'] = esc_js(get_option('caa_effect6_origin_y', '100'));
                break;
            // Effects 3 and 7 don't need additional settings
        }
        
        // Pass settings to JavaScript
        wp_localize_script('caa-frontend', 'caaSettings', $settings_array);
        
        // Enqueue frontend CSS if needed
        wp_enqueue_style(
            'caa-frontend',
            CAA_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            CAA_VERSION
        );
    }
}

// Initialize the plugin
Context_Aware_Animation::get_instance();

