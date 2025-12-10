<?php
/**
 * License Manager for Logo Collision Pro
 * 
 * Integrates with License Manager for WooCommerce REST API
 * 
 * @package Logo_Collision_Pro
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CAA_License_Manager
 * 
 * Handles license key validation and storage for Pro version updates
 */
class CAA_License_Manager {
    
    /**
     * License server URL
     */
    const LICENSE_SERVER = 'https://exzent.de';
    
    /**
     * Option name for storing license data
     */
    const OPTION_NAME = 'caa_pro_license';
    
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
        add_action('admin_init', array($this, 'handle_license_actions'));
        add_action('admin_notices', array($this, 'license_notices'));
    }
    
    /**
     * Get stored license data
     */
    public function get_license_data() {
        return get_option(self::OPTION_NAME, array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'last_check' => 0
        ));
    }
    
    /**
     * Save license data
     */
    public function save_license_data($data) {
        return update_option(self::OPTION_NAME, $data);
    }
    
    /**
     * Get license key
     */
    public function get_license_key() {
        $data = $this->get_license_data();
        return isset($data['key']) ? $data['key'] : '';
    }
    
    /**
     * Check if license is active
     */
    public function is_license_active() {
        $data = $this->get_license_data();
        return isset($data['status']) && $data['status'] === 'active';
    }
    
    /**
     * Validate license with server
     */
    public function validate_license($license_key) {
        $response = wp_remote_get(
            self::LICENSE_SERVER . '/wp-json/lmfwc/v2/licenses/validate/' . urlencode($license_key),
            array(
                'timeout' => 15,
                'headers' => array(
                    'Accept' => 'application/json'
                )
            )
        );
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['success']) && $data['success']) {
            return array(
                'success' => true,
                'status' => 'active',
                'expires' => isset($data['data']['expiresAt']) ? $data['data']['expiresAt'] : ''
            );
        }
        
        return array(
            'success' => false,
            'message' => isset($data['message']) ? $data['message'] : __('Invalid license key.', 'logo-collision')
        );
    }
    
    /**
     * Activate license
     */
    public function activate_license($license_key) {
        $result = $this->validate_license($license_key);
        
        if ($result['success']) {
            $this->save_license_data(array(
                'key' => sanitize_text_field($license_key),
                'status' => 'active',
                'expires' => isset($result['expires']) ? $result['expires'] : '',
                'last_check' => time()
            ));
            
            return array(
                'success' => true,
                'message' => __('License activated successfully!', 'logo-collision')
            );
        }
        
        return $result;
    }
    
    /**
     * Deactivate license
     */
    public function deactivate_license() {
        $this->save_license_data(array(
            'key' => '',
            'status' => 'inactive',
            'expires' => '',
            'last_check' => 0
        ));
        
        return array(
            'success' => true,
            'message' => __('License deactivated.', 'logo-collision')
        );
    }
    
    /**
     * Handle license form submissions
     */
    public function handle_license_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Activate license
        if (isset($_POST['caa_activate_license']) && check_admin_referer('caa_license_nonce')) {
            $license_key = isset($_POST['caa_license_key']) ? sanitize_text_field(wp_unslash($_POST['caa_license_key'])) : '';
            
            if (!empty($license_key)) {
                $result = $this->activate_license($license_key);
                if ($result['success']) {
                    add_settings_error('caa_license', 'license_activated', $result['message'], 'success');
                } else {
                    add_settings_error('caa_license', 'license_error', $result['message'], 'error');
                }
            }
        }
        
        // Deactivate license
        if (isset($_POST['caa_deactivate_license']) && check_admin_referer('caa_license_nonce')) {
            $result = $this->deactivate_license();
            add_settings_error('caa_license', 'license_deactivated', $result['message'], 'updated');
        }
    }
    
    /**
     * Show license notices
     */
    public function license_notices() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'settings_page_logo-collision') {
            settings_errors('caa_license');
        }
    }
    
    /**
     * Render license settings field (for use in settings page)
     */
    public function render_license_field() {
        $license_data = $this->get_license_data();
        $license_key = $license_data['key'];
        $is_active = $this->is_license_active();
        ?>
        <div class="caa-license-field">
            <?php wp_nonce_field('caa_license_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="caa_license_key"><?php esc_html_e('License Key', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="password" 
                            id="caa_license_key" 
                            name="caa_license_key" 
                            value="<?php echo esc_attr($license_key); ?>" 
                            class="regular-text"
                            <?php echo $is_active ? 'readonly' : ''; ?>
                        />
                        <?php if ($is_active) : ?>
                            <span class="caa-license-status caa-license-active">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Active', 'logo-collision'); ?>
                            </span>
                            <button type="submit" name="caa_deactivate_license" class="button button-secondary">
                                <?php esc_html_e('Deactivate', 'logo-collision'); ?>
                            </button>
                        <?php else : ?>
                            <button type="submit" name="caa_activate_license" class="button button-primary">
                                <?php esc_html_e('Activate License', 'logo-collision'); ?>
                            </button>
                        <?php endif; ?>
                        <p class="description">
                            <?php esc_html_e('Enter your license key to receive automatic updates.', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}

// Initialize
CAA_License_Manager::get_instance();
