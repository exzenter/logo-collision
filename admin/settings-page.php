<?php
/**
 * Settings page for Context-Aware Animation plugin
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check user capabilities
if (!current_user_can('manage_options')) {
    return;
}

/**
 * Custom sanitization functions (matching those in main plugin file)
 */
function caa_sanitize_effect($value) {
    $valid_effects = array('1', '2', '3', '4', '5', '6', '7');
    return in_array($value, $valid_effects, true) ? $value : '1';
}

function caa_sanitize_offset($value) {
    $value = trim($value);
    if ($value === '' || $value === null) {
        return '0';
    }
    return (string) intval($value);
}

function caa_sanitize_float($value) {
    $value = trim($value);
    if ($value === '' || $value === null) {
        return '0';
    }
    return (string) floatval($value);
}

function caa_sanitize_percent($value) {
    $value = trim($value);
    if ($value === '' || $value === null) {
        return '0';
    }
    $int_value = intval($value);
    $int_value = max(0, min(100, $int_value));
    return (string) $int_value;
}

function caa_sanitize_move_away($value) {
    $value = trim($value);
    if ($value === '' || $value === null) {
        return '';
    }
    // Check if it ends with % or px
    if (preg_match('/^([+-]?\d+(?:\.\d+)?)(px|%)$/i', $value, $matches)) {
        $number = floatval($matches[1]);
        $unit = strtolower($matches[2]);
        return (string) $number . $unit;
    }
    // If no unit, assume px
    $number = floatval($value);
    return (string) $number . 'px';
}

function caa_sanitize_ease($value) {
    $valid_eases = array('power1', 'power2', 'power3', 'power4', 'expo', 'sine', 'back', 'elastic', 'bounce', 'none');
    return in_array($value, $valid_eases, true) ? $value : 'power4';
}

// Get plugin instance early for form handlers
$plugin_instance = Context_Aware_Animation::get_instance();

// Helper function to parse effect mappings from POST data
function caa_parse_mappings_from_post($mappings_data) {
    $mappings = array();
    foreach ($mappings_data as $mapping) {
        $selector = isset($mapping['selector']) ? sanitize_text_field($mapping['selector']) : '';
        $effect = isset($mapping['effect']) ? caa_sanitize_effect(sanitize_text_field($mapping['effect'])) : '1';
        $override_enabled = isset($mapping['override_enabled']) && $mapping['override_enabled'] === '1';
        
        if (!empty($selector)) {
            $mapping_data = array(
                'selector' => $selector,
                'effect' => $effect,
                'override_enabled' => $override_enabled
            );
            
            if ($override_enabled && isset($mapping['settings']) && is_array($mapping['settings'])) {
                $settings = $mapping['settings'];
                $mapping_data['settings'] = array(
                    'duration' => isset($settings['duration']) ? caa_sanitize_float(sanitize_text_field($settings['duration'])) : '0.6',
                    'ease' => isset($settings['ease']) ? caa_sanitize_ease(sanitize_text_field($settings['ease'])) : 'power4',
                    'offset_start' => isset($settings['offset_start']) ? caa_sanitize_offset(sanitize_text_field($settings['offset_start'])) : '30',
                    'offset_end' => isset($settings['offset_end']) ? caa_sanitize_offset(sanitize_text_field($settings['offset_end'])) : '10',
                );
                
                switch ($effect) {
                    case '1':
                        $mapping_data['settings']['effect1_scale_down'] = isset($settings['effect1_scale_down']) ? caa_sanitize_float(sanitize_text_field($settings['effect1_scale_down'])) : '0';
                        $mapping_data['settings']['effect1_origin_x'] = isset($settings['effect1_origin_x']) ? caa_sanitize_percent(sanitize_text_field($settings['effect1_origin_x'])) : '0';
                        $mapping_data['settings']['effect1_origin_y'] = isset($settings['effect1_origin_y']) ? caa_sanitize_percent(sanitize_text_field($settings['effect1_origin_y'])) : '50';
                        break;
                    case '2':
                        $mapping_data['settings']['effect2_blur_amount'] = isset($settings['effect2_blur_amount']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_amount'])) : '5';
                        $mapping_data['settings']['effect2_blur_scale'] = isset($settings['effect2_blur_scale']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_scale'])) : '0.9';
                        $mapping_data['settings']['effect2_blur_duration'] = isset($settings['effect2_blur_duration']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_duration'])) : '0.2';
                        break;
                    case '4':
                        $mapping_data['settings']['effect4_text_x_range'] = isset($settings['effect4_text_x_range']) ? caa_sanitize_offset(sanitize_text_field($settings['effect4_text_x_range'])) : '50';
                        $mapping_data['settings']['effect4_text_y_range'] = isset($settings['effect4_text_y_range']) ? caa_sanitize_offset(sanitize_text_field($settings['effect4_text_y_range'])) : '40';
                        $mapping_data['settings']['effect4_stagger_amount'] = isset($settings['effect4_stagger_amount']) ? caa_sanitize_float(sanitize_text_field($settings['effect4_stagger_amount'])) : '0.03';
                        break;
                    case '5':
                        $mapping_data['settings']['effect5_shuffle_iterations'] = isset($settings['effect5_shuffle_iterations']) ? caa_sanitize_offset(sanitize_text_field($settings['effect5_shuffle_iterations'])) : '2';
                        $mapping_data['settings']['effect5_shuffle_duration'] = isset($settings['effect5_shuffle_duration']) ? caa_sanitize_float(sanitize_text_field($settings['effect5_shuffle_duration'])) : '0.03';
                        $mapping_data['settings']['effect5_char_delay'] = isset($settings['effect5_char_delay']) ? caa_sanitize_float(sanitize_text_field($settings['effect5_char_delay'])) : '0.03';
                        break;
                    case '6':
                        $mapping_data['settings']['effect6_rotation'] = isset($settings['effect6_rotation']) ? caa_sanitize_offset(sanitize_text_field($settings['effect6_rotation'])) : '-90';
                        $mapping_data['settings']['effect6_x_percent'] = isset($settings['effect6_x_percent']) ? caa_sanitize_offset(sanitize_text_field($settings['effect6_x_percent'])) : '-5';
                        $mapping_data['settings']['effect6_origin_x'] = isset($settings['effect6_origin_x']) ? caa_sanitize_percent(sanitize_text_field($settings['effect6_origin_x'])) : '0';
                        $mapping_data['settings']['effect6_origin_y'] = isset($settings['effect6_origin_y']) ? caa_sanitize_percent(sanitize_text_field($settings['effect6_origin_y'])) : '100';
                        break;
                    case '7':
                        $mapping_data['settings']['effect7_move_distance'] = isset($settings['effect7_move_distance']) ? caa_sanitize_move_away(sanitize_text_field($settings['effect7_move_distance'])) : '';
                        break;
                }
            }
            
            $mappings[] = $mapping_data;
        }
    }
    return $mappings;
}

// Handle per-instance Mappings form submission
if (isset($_POST['caa_save_instance_mappings']) && check_admin_referer('caa_instance_mappings_nonce')) {
    $instance_id = isset($_POST['caa_instance_id']) ? absint($_POST['caa_instance_id']) : 1;
    $instance = $plugin_instance->get_logo_instance($instance_id);
    
    if ($instance) {
        $mappings = array();
        if (isset($_POST['caa_mappings']) && is_array($_POST['caa_mappings'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $mappings = caa_parse_mappings_from_post(wp_unslash($_POST['caa_mappings']));
        }
        $instance['effect_mappings'] = $mappings;
        $plugin_instance->save_instance($instance_id, $instance);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Element mappings saved.', 'logo-collision') . '</p></div>';
    }
}

// Handle per-instance Filtering form submission
if (isset($_POST['caa_save_instance_filtering']) && check_admin_referer('caa_instance_filtering_nonce')) {
    $instance_id = isset($_POST['caa_instance_id']) ? absint($_POST['caa_instance_id']) : 1;
    $instance = $plugin_instance->get_logo_instance($instance_id);
    
    if ($instance) {
        $instance['enable_filtering'] = isset($_POST['caa_instance_enable_filtering']) ? '1' : '0';
        $instance['filter_mode'] = isset($_POST['caa_instance_filter_mode']) ? sanitize_text_field(wp_unslash($_POST['caa_instance_filter_mode'])) : 'include';
        
        // Handle post types
        $selected_post_types = array();
        if (isset($_POST['caa_instance_post_types']) && is_array($_POST['caa_instance_post_types'])) {
            $valid_post_types = array_keys(get_post_types(array('public' => true), 'names'));
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $post_types_data = wp_unslash($_POST['caa_instance_post_types']);
            foreach ($post_types_data as $post_type) {
                $post_type = sanitize_text_field($post_type);
                if (in_array($post_type, $valid_post_types, true)) {
                    $selected_post_types[] = $post_type;
                }
            }
        }
        $instance['selected_post_types'] = $selected_post_types;
        
        $instance['include_pages'] = isset($_POST['caa_instance_include_pages']) ? '1' : '0';
        $instance['include_posts'] = isset($_POST['caa_instance_include_posts']) ? '1' : '0';
        
        // Handle selected items
        $selected_items = array();
        if (isset($_POST['caa_instance_selected_items']) && is_array($_POST['caa_instance_selected_items'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $items_data = wp_unslash($_POST['caa_instance_selected_items']);
            foreach ($items_data as $item_id) {
                $item_id = absint($item_id);
                if ($item_id > 0) {
                    $selected_items[] = $item_id;
                }
            }
        }
        $instance['selected_items'] = array_unique($selected_items);
        
        $plugin_instance->save_instance($instance_id, $instance);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Page filtering saved.', 'logo-collision') . '</p></div>';
    }
}

// Handle Instance Delete
if (isset($_POST['caa_delete_instance']) && check_admin_referer('caa_instance_nonce')) {
    $instance_id = isset($_POST['caa_instance_id']) ? absint($_POST['caa_instance_id']) : 0;
    if ($instance_id > 0) {
        $plugin_instance->delete_instance($instance_id);
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Instance deleted.', 'logo-collision') . '</p></div>';
    }
}

// Handle Instance Save (Create/Update)
if (isset($_POST['caa_save_instance']) && check_admin_referer('caa_instance_nonce')) {
    $instance_id = isset($_POST['caa_instance_id']) ? absint($_POST['caa_instance_id']) : 0;
    $is_new = ($instance_id === 0);
    
    // Check max instances for new instances
    if ($is_new) {
        $instances = $plugin_instance->get_all_instances();
        if (count($instances) >= CAA_MAX_INSTANCES) {
            /* translators: %d: Maximum number of instances */
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html(sprintf(__('Maximum of %d instances reached.', 'logo-collision'), CAA_MAX_INSTANCES)) . '</p></div>';
        } else {
            $instance_id = $plugin_instance->get_next_instance_id();
        }
    }
    
    if ($instance_id > 0) {
        // Build instance data from POST
        $instance_data = array(
            'enabled' => isset($_POST['caa_instance_enabled']),
            'logo_id' => isset($_POST['caa_instance_logo_id']) ? sanitize_text_field(wp_unslash($_POST['caa_instance_logo_id'])) : '',
            'selected_effect' => isset($_POST['caa_instance_effect']) ? caa_sanitize_effect(sanitize_text_field(wp_unslash($_POST['caa_instance_effect']))) : '1',
            'included_elements' => isset($_POST['caa_instance_included']) ? sanitize_textarea_field(wp_unslash($_POST['caa_instance_included'])) : '',
            'excluded_elements' => isset($_POST['caa_instance_excluded']) ? sanitize_textarea_field(wp_unslash($_POST['caa_instance_excluded'])) : '',
            'global_offset' => isset($_POST['caa_instance_global_offset']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_global_offset']))) : '0',
            'debug_mode' => isset($_POST['caa_instance_debug']) ? '1' : '0',
            'duration' => isset($_POST['caa_instance_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_duration']))) : '0.6',
            'ease' => isset($_POST['caa_instance_ease']) ? caa_sanitize_ease(sanitize_text_field(wp_unslash($_POST['caa_instance_ease']))) : 'power4',
            'offset_start' => isset($_POST['caa_instance_offset_start']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_offset_start']))) : '30',
            'offset_end' => isset($_POST['caa_instance_offset_end']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_offset_end']))) : '10',
            // Effect settings
            'effect1_scale_down' => isset($_POST['caa_instance_effect1_scale_down']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect1_scale_down']))) : '0',
            'effect1_origin_x' => isset($_POST['caa_instance_effect1_origin_x']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_instance_effect1_origin_x']))) : '0',
            'effect1_origin_y' => isset($_POST['caa_instance_effect1_origin_y']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_instance_effect1_origin_y']))) : '50',
            'effect2_blur_amount' => isset($_POST['caa_instance_effect2_blur_amount']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect2_blur_amount']))) : '5',
            'effect2_blur_scale' => isset($_POST['caa_instance_effect2_blur_scale']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect2_blur_scale']))) : '0.9',
            'effect2_blur_duration' => isset($_POST['caa_instance_effect2_blur_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect2_blur_duration']))) : '0.2',
            'effect4_text_x_range' => isset($_POST['caa_instance_effect4_text_x_range']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_effect4_text_x_range']))) : '50',
            'effect4_text_y_range' => isset($_POST['caa_instance_effect4_text_y_range']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_effect4_text_y_range']))) : '40',
            'effect4_stagger_amount' => isset($_POST['caa_instance_effect4_stagger_amount']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect4_stagger_amount']))) : '0.03',
            'effect5_shuffle_iterations' => isset($_POST['caa_instance_effect5_shuffle_iterations']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_effect5_shuffle_iterations']))) : '2',
            'effect5_shuffle_duration' => isset($_POST['caa_instance_effect5_shuffle_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect5_shuffle_duration']))) : '0.03',
            'effect5_char_delay' => isset($_POST['caa_instance_effect5_char_delay']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_instance_effect5_char_delay']))) : '0.03',
            'effect6_rotation' => isset($_POST['caa_instance_effect6_rotation']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_effect6_rotation']))) : '-90',
            'effect6_x_percent' => isset($_POST['caa_instance_effect6_x_percent']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_instance_effect6_x_percent']))) : '-5',
            'effect6_origin_x' => isset($_POST['caa_instance_effect6_origin_x']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_instance_effect6_origin_x']))) : '0',
            'effect6_origin_y' => isset($_POST['caa_instance_effect6_origin_y']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_instance_effect6_origin_y']))) : '100',
            'effect7_move_distance' => isset($_POST['caa_instance_effect7_move_distance']) ? caa_sanitize_move_away(sanitize_text_field(wp_unslash($_POST['caa_instance_effect7_move_distance']))) : '',
            // Pro features per instance - effect mappings
            'effect_mappings' => array(),
            // Filtering settings
            'enable_filtering' => isset($_POST['caa_instance_enable_filtering']) ? '1' : '0',
            'filter_mode' => isset($_POST['caa_instance_filter_mode']) ? sanitize_text_field(wp_unslash($_POST['caa_instance_filter_mode'])) : 'include',
            'selected_post_types' => array(),
            'include_pages' => isset($_POST['caa_instance_include_pages']) ? '1' : '0',
            'include_posts' => isset($_POST['caa_instance_include_posts']) ? '1' : '0',
            'selected_items' => array(),
        );
        
        // Handle effect mappings for this instance
        if (isset($_POST['caa_instance_mappings']) && is_array($_POST['caa_instance_mappings'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization occurs below
            $inst_mappings = wp_unslash($_POST['caa_instance_mappings']);
            foreach ($inst_mappings as $mapping) {
                $selector = isset($mapping['selector']) ? sanitize_text_field($mapping['selector']) : '';
                if (!empty($selector)) {
                    $effect = isset($mapping['effect']) ? caa_sanitize_effect(sanitize_text_field($mapping['effect'])) : '1';
                    $override_enabled = isset($mapping['override_enabled']) && $mapping['override_enabled'] === '1';
                    
                    $mapping_data = array(
                        'selector' => $selector,
                        'effect' => $effect,
                        'override_enabled' => $override_enabled
                    );
                    
                    if ($override_enabled && isset($mapping['settings']) && is_array($mapping['settings'])) {
                        $settings = $mapping['settings'];
                        $mapping_data['settings'] = array(
                            'duration' => isset($settings['duration']) ? caa_sanitize_float(sanitize_text_field($settings['duration'])) : '0.6',
                            'ease' => isset($settings['ease']) ? caa_sanitize_ease(sanitize_text_field($settings['ease'])) : 'power4',
                            'offset_start' => isset($settings['offset_start']) ? caa_sanitize_offset(sanitize_text_field($settings['offset_start'])) : '30',
                            'offset_end' => isset($settings['offset_end']) ? caa_sanitize_offset(sanitize_text_field($settings['offset_end'])) : '10',
                        );
                        
                        // Effect-specific settings
                        switch ($effect) {
                            case '1':
                                $mapping_data['settings']['effect1_scale_down'] = isset($settings['effect1_scale_down']) ? caa_sanitize_float(sanitize_text_field($settings['effect1_scale_down'])) : '0';
                                $mapping_data['settings']['effect1_origin_x'] = isset($settings['effect1_origin_x']) ? caa_sanitize_percent(sanitize_text_field($settings['effect1_origin_x'])) : '0';
                                $mapping_data['settings']['effect1_origin_y'] = isset($settings['effect1_origin_y']) ? caa_sanitize_percent(sanitize_text_field($settings['effect1_origin_y'])) : '50';
                                break;
                            case '2':
                                $mapping_data['settings']['effect2_blur_amount'] = isset($settings['effect2_blur_amount']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_amount'])) : '5';
                                $mapping_data['settings']['effect2_blur_scale'] = isset($settings['effect2_blur_scale']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_scale'])) : '0.9';
                                $mapping_data['settings']['effect2_blur_duration'] = isset($settings['effect2_blur_duration']) ? caa_sanitize_float(sanitize_text_field($settings['effect2_blur_duration'])) : '0.2';
                                break;
                            case '4':
                                $mapping_data['settings']['effect4_text_x_range'] = isset($settings['effect4_text_x_range']) ? caa_sanitize_offset(sanitize_text_field($settings['effect4_text_x_range'])) : '50';
                                $mapping_data['settings']['effect4_text_y_range'] = isset($settings['effect4_text_y_range']) ? caa_sanitize_offset(sanitize_text_field($settings['effect4_text_y_range'])) : '40';
                                $mapping_data['settings']['effect4_stagger_amount'] = isset($settings['effect4_stagger_amount']) ? caa_sanitize_float(sanitize_text_field($settings['effect4_stagger_amount'])) : '0.03';
                                break;
                            case '5':
                                $mapping_data['settings']['effect5_shuffle_iterations'] = isset($settings['effect5_shuffle_iterations']) ? caa_sanitize_offset(sanitize_text_field($settings['effect5_shuffle_iterations'])) : '2';
                                $mapping_data['settings']['effect5_shuffle_duration'] = isset($settings['effect5_shuffle_duration']) ? caa_sanitize_float(sanitize_text_field($settings['effect5_shuffle_duration'])) : '0.03';
                                $mapping_data['settings']['effect5_char_delay'] = isset($settings['effect5_char_delay']) ? caa_sanitize_float(sanitize_text_field($settings['effect5_char_delay'])) : '0.03';
                                break;
                            case '6':
                                $mapping_data['settings']['effect6_rotation'] = isset($settings['effect6_rotation']) ? caa_sanitize_offset(sanitize_text_field($settings['effect6_rotation'])) : '-90';
                                $mapping_data['settings']['effect6_x_percent'] = isset($settings['effect6_x_percent']) ? caa_sanitize_offset(sanitize_text_field($settings['effect6_x_percent'])) : '-5';
                                $mapping_data['settings']['effect6_origin_x'] = isset($settings['effect6_origin_x']) ? caa_sanitize_percent(sanitize_text_field($settings['effect6_origin_x'])) : '0';
                                $mapping_data['settings']['effect6_origin_y'] = isset($settings['effect6_origin_y']) ? caa_sanitize_percent(sanitize_text_field($settings['effect6_origin_y'])) : '100';
                                break;
                            case '7':
                                $mapping_data['settings']['effect7_move_distance'] = isset($settings['effect7_move_distance']) ? caa_sanitize_move_away(sanitize_text_field($settings['effect7_move_distance'])) : '';
                                break;
                        }
                    }
                    
                    $instance_data['effect_mappings'][] = $mapping_data;
                }
            }
        }
        
        // Handle selected post types for filtering
        if (isset($_POST['caa_instance_post_types']) && is_array($_POST['caa_instance_post_types'])) {
            $valid_post_types = array_keys(get_post_types(array('public' => true), 'names'));
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $inst_post_types = wp_unslash($_POST['caa_instance_post_types']);
            foreach ($inst_post_types as $post_type) {
                $post_type = sanitize_text_field($post_type);
                if (in_array($post_type, $valid_post_types, true)) {
                    $instance_data['selected_post_types'][] = $post_type;
                }
            }
        }
        
        // Handle selected items for filtering
        if (isset($_POST['caa_instance_selected_items']) && is_array($_POST['caa_instance_selected_items'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            $inst_items = wp_unslash($_POST['caa_instance_selected_items']);
            foreach ($inst_items as $item_id) {
                $item_id = absint($item_id);
                if ($item_id > 0) {
                    $instance_data['selected_items'][] = $item_id;
                }
            }
            $instance_data['selected_items'] = array_unique($instance_data['selected_items']);
        }
        
        $plugin_instance->save_instance($instance_id, $instance_data);
        
        $message = $is_new ? __('Instance created.', 'logo-collision') : __('Instance saved.', 'logo-collision');
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
}

// Get instances data
$all_instances = $plugin_instance->get_all_instances();
$instance_count = count($all_instances);
$can_add_instance = $instance_count < CAA_MAX_INSTANCES;

// Get selected instance ID from URL parameter (defaults to 1)
$selected_instance_id = isset($_GET['instance_id']) ? absint($_GET['instance_id']) : 1;

// Special case: instance_id=0 means creating a new instance
$creating_new_instance = ($selected_instance_id === 0);

if ($creating_new_instance) {
    // Creating new instance - use defaults
    $next_id = $plugin_instance->get_next_instance_id();
    $selected_instance_id = 0; // Keep as 0 for form handling
    $selected_instance = $plugin_instance->get_default_instance_data();
    $selected_instance['logo_id'] = '';
} else {
    // Ensure selected instance exists, fallback to first available
    if (!isset($all_instances[$selected_instance_id])) {
        $selected_instance_id = !empty($all_instances) ? array_key_first($all_instances) : 1;
    }
    $selected_instance = $plugin_instance->get_logo_instance($selected_instance_id);
    
    // If still no instance (shouldn't happen due to ensure_instance_exists), use defaults
    if (!$selected_instance) {
        $selected_instance = $plugin_instance->get_default_instance_data();
    }
}

// For backward compatibility - keep editing_instance variables
$editing_instance_id = $selected_instance_id;
$editing_instance = $selected_instance;

// Handle General Settings form submission
if (isset($_POST['caa_save_settings']) && check_admin_referer('caa_settings_nonce')) {
    // Core settings with isset() checks and proper sanitization
    update_option('caa_logo_id', isset($_POST['caa_logo_id']) ? sanitize_text_field(wp_unslash($_POST['caa_logo_id'])) : '');
    update_option('caa_selected_effect', isset($_POST['caa_selected_effect']) ? caa_sanitize_effect(sanitize_text_field(wp_unslash($_POST['caa_selected_effect']))) : '1');
    update_option('caa_included_elements', isset($_POST['caa_included_elements']) ? sanitize_textarea_field(wp_unslash($_POST['caa_included_elements'])) : '');
    update_option('caa_excluded_elements', isset($_POST['caa_excluded_elements']) ? sanitize_textarea_field(wp_unslash($_POST['caa_excluded_elements'])) : '');
    update_option('caa_global_offset', isset($_POST['caa_global_offset']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_global_offset']))) : '0');
    update_option('caa_debug_mode', isset($_POST['caa_debug_mode']) ? '1' : '0');
    
    // Mobile disable settings
    update_option('caa_disable_mobile', isset($_POST['caa_disable_mobile']) ? '1' : '0');
    update_option('caa_mobile_breakpoint', isset($_POST['caa_mobile_breakpoint']) ? absint(wp_unslash($_POST['caa_mobile_breakpoint'])) : '768');
    
    // Global animation settings
    update_option('caa_duration', isset($_POST['caa_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_duration']))) : '0.6');
    update_option('caa_ease', isset($_POST['caa_ease']) ? caa_sanitize_ease(sanitize_text_field(wp_unslash($_POST['caa_ease']))) : 'power4');
    update_option('caa_offset_start', isset($_POST['caa_offset_start']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_offset_start']))) : '30');
    update_option('caa_offset_end', isset($_POST['caa_offset_end']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_offset_end']))) : '10');
    
    // Effect 1: Scale
    update_option('caa_effect1_scale_down', isset($_POST['caa_effect1_scale_down']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect1_scale_down']))) : '0');
    update_option('caa_effect1_origin_x', isset($_POST['caa_effect1_origin_x']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_effect1_origin_x']))) : '0');
    update_option('caa_effect1_origin_y', isset($_POST['caa_effect1_origin_y']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_effect1_origin_y']))) : '50');
    
    // Effect 2: Blur
    update_option('caa_effect2_blur_amount', isset($_POST['caa_effect2_blur_amount']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect2_blur_amount']))) : '5');
    update_option('caa_effect2_blur_scale', isset($_POST['caa_effect2_blur_scale']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect2_blur_scale']))) : '0.9');
    update_option('caa_effect2_blur_duration', isset($_POST['caa_effect2_blur_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect2_blur_duration']))) : '0.2');
    
    // Effect 4: Text Split
    update_option('caa_effect4_text_x_range', isset($_POST['caa_effect4_text_x_range']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_effect4_text_x_range']))) : '50');
    update_option('caa_effect4_text_y_range', isset($_POST['caa_effect4_text_y_range']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_effect4_text_y_range']))) : '40');
    update_option('caa_effect4_stagger_amount', isset($_POST['caa_effect4_stagger_amount']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect4_stagger_amount']))) : '0.03');
    
    // Effect 5: Character Shuffle
    update_option('caa_effect5_shuffle_iterations', isset($_POST['caa_effect5_shuffle_iterations']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_effect5_shuffle_iterations']))) : '2');
    update_option('caa_effect5_shuffle_duration', isset($_POST['caa_effect5_shuffle_duration']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect5_shuffle_duration']))) : '0.03');
    update_option('caa_effect5_char_delay', isset($_POST['caa_effect5_char_delay']) ? caa_sanitize_float(sanitize_text_field(wp_unslash($_POST['caa_effect5_char_delay']))) : '0.03');
    
    // Effect 6: Rotation
    update_option('caa_effect6_rotation', isset($_POST['caa_effect6_rotation']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_effect6_rotation']))) : '-90');
    update_option('caa_effect6_x_percent', isset($_POST['caa_effect6_x_percent']) ? caa_sanitize_offset(sanitize_text_field(wp_unslash($_POST['caa_effect6_x_percent']))) : '-5');
    update_option('caa_effect6_origin_x', isset($_POST['caa_effect6_origin_x']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_effect6_origin_x']))) : '0');
    update_option('caa_effect6_origin_y', isset($_POST['caa_effect6_origin_y']) ? caa_sanitize_percent(sanitize_text_field(wp_unslash($_POST['caa_effect6_origin_y']))) : '100');
    
    // Effect 7: Move Away
    update_option('caa_effect7_move_distance', isset($_POST['caa_effect7_move_distance']) ? caa_sanitize_move_away(sanitize_text_field(wp_unslash($_POST['caa_effect7_move_distance']))) : '');
    
    // Also sync these settings to Instance 1 for consistency
    $instance_1 = $plugin_instance->get_logo_instance(1);
    if ($instance_1) {
        $instance_1['logo_id'] = get_option('caa_logo_id', '');
        $instance_1['selected_effect'] = get_option('caa_selected_effect', '1');
        $instance_1['included_elements'] = get_option('caa_included_elements', '');
        $instance_1['excluded_elements'] = get_option('caa_excluded_elements', '');
        $instance_1['global_offset'] = get_option('caa_global_offset', '0');
        $instance_1['debug_mode'] = get_option('caa_debug_mode', '0');
        $instance_1['duration'] = get_option('caa_duration', '0.6');
        $instance_1['ease'] = get_option('caa_ease', 'power4');
        $instance_1['offset_start'] = get_option('caa_offset_start', '30');
        $instance_1['offset_end'] = get_option('caa_offset_end', '10');
        $instance_1['effect1_scale_down'] = get_option('caa_effect1_scale_down', '0');
        $instance_1['effect1_origin_x'] = get_option('caa_effect1_origin_x', '0');
        $instance_1['effect1_origin_y'] = get_option('caa_effect1_origin_y', '50');
        $instance_1['effect2_blur_amount'] = get_option('caa_effect2_blur_amount', '5');
        $instance_1['effect2_blur_scale'] = get_option('caa_effect2_blur_scale', '0.9');
        $instance_1['effect2_blur_duration'] = get_option('caa_effect2_blur_duration', '0.2');
        $instance_1['effect4_text_x_range'] = get_option('caa_effect4_text_x_range', '50');
        $instance_1['effect4_text_y_range'] = get_option('caa_effect4_text_y_range', '40');
        $instance_1['effect4_stagger_amount'] = get_option('caa_effect4_stagger_amount', '0.03');
        $instance_1['effect5_shuffle_iterations'] = get_option('caa_effect5_shuffle_iterations', '2');
        $instance_1['effect5_shuffle_duration'] = get_option('caa_effect5_shuffle_duration', '0.03');
        $instance_1['effect5_char_delay'] = get_option('caa_effect5_char_delay', '0.03');
        $instance_1['effect6_rotation'] = get_option('caa_effect6_rotation', '-90');
        $instance_1['effect6_x_percent'] = get_option('caa_effect6_x_percent', '-5');
        $instance_1['effect6_origin_x'] = get_option('caa_effect6_origin_x', '0');
        $instance_1['effect6_origin_y'] = get_option('caa_effect6_origin_y', '100');
        $instance_1['effect7_move_distance'] = get_option('caa_effect7_move_distance', '');
        $plugin_instance->save_instance(1, $instance_1);
    }
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'logo-collision') . '</p></div>';
}

// Get current settings from Instance 1 (for main General Settings tab)
$instance_1 = $plugin_instance->get_logo_instance(1);
if (!$instance_1) {
    $instance_1 = $plugin_instance->get_default_instance_data();
}

$logo_id = isset($instance_1['logo_id']) ? $instance_1['logo_id'] : '';
$selected_effect = isset($instance_1['selected_effect']) ? $instance_1['selected_effect'] : '1';
$included_elements = isset($instance_1['included_elements']) ? $instance_1['included_elements'] : '';
$excluded_elements = isset($instance_1['excluded_elements']) ? $instance_1['excluded_elements'] : '';
$global_offset = isset($instance_1['global_offset']) ? $instance_1['global_offset'] : '0';
$debug_mode = isset($instance_1['debug_mode']) ? $instance_1['debug_mode'] : '0';

// Get mobile disable settings (these remain global - apply to all instances)
$disable_mobile = get_option('caa_disable_mobile', '0');
$mobile_breakpoint = get_option('caa_mobile_breakpoint', '768');

// Get animation settings from Instance 1
$duration = isset($instance_1['duration']) ? $instance_1['duration'] : '0.6';
$ease = isset($instance_1['ease']) ? $instance_1['ease'] : 'power4';
$offset_start = isset($instance_1['offset_start']) ? $instance_1['offset_start'] : '30';
$offset_end = isset($instance_1['offset_end']) ? $instance_1['offset_end'] : '10';

// Get effect-specific settings from Instance 1
$effect1_scale_down = isset($instance_1['effect1_scale_down']) ? $instance_1['effect1_scale_down'] : '0';
$effect1_origin_x = isset($instance_1['effect1_origin_x']) ? $instance_1['effect1_origin_x'] : '0';
$effect1_origin_y = isset($instance_1['effect1_origin_y']) ? $instance_1['effect1_origin_y'] : '50';

$effect2_blur_amount = isset($instance_1['effect2_blur_amount']) ? $instance_1['effect2_blur_amount'] : '5';
$effect2_blur_scale = isset($instance_1['effect2_blur_scale']) ? $instance_1['effect2_blur_scale'] : '0.9';
$effect2_blur_duration = isset($instance_1['effect2_blur_duration']) ? $instance_1['effect2_blur_duration'] : '0.2';

$effect4_text_x_range = isset($instance_1['effect4_text_x_range']) ? $instance_1['effect4_text_x_range'] : '50';
$effect4_text_y_range = isset($instance_1['effect4_text_y_range']) ? $instance_1['effect4_text_y_range'] : '40';
$effect4_stagger_amount = isset($instance_1['effect4_stagger_amount']) ? $instance_1['effect4_stagger_amount'] : '0.03';

$effect5_shuffle_iterations = isset($instance_1['effect5_shuffle_iterations']) ? $instance_1['effect5_shuffle_iterations'] : '2';
$effect5_shuffle_duration = isset($instance_1['effect5_shuffle_duration']) ? $instance_1['effect5_shuffle_duration'] : '0.03';
$effect5_char_delay = isset($instance_1['effect5_char_delay']) ? $instance_1['effect5_char_delay'] : '0.03';

$effect6_rotation = isset($instance_1['effect6_rotation']) ? $instance_1['effect6_rotation'] : '-90';
$effect6_x_percent = isset($instance_1['effect6_x_percent']) ? $instance_1['effect6_x_percent'] : '-5';
$effect6_origin_x = isset($instance_1['effect6_origin_x']) ? $instance_1['effect6_origin_x'] : '0';
$effect6_origin_y = isset($instance_1['effect6_origin_y']) ? $instance_1['effect6_origin_y'] : '100';

$effect7_move_distance = isset($instance_1['effect7_move_distance']) ? $instance_1['effect7_move_distance'] : '';

// Get all post types for filtering options
$all_post_types = get_post_types(array('public' => true), 'objects');

// Enqueue admin CSS
wp_enqueue_style(
    'caa-admin',
    CAA_PLUGIN_URL . 'assets/css/admin.css',
    array(),
    CAA_VERSION
);

// Enqueue jQuery UI autocomplete
wp_enqueue_script('jquery-ui-autocomplete');

// Enqueue admin JavaScript for accordion functionality
wp_enqueue_script(
    'caa-admin',
    CAA_PLUGIN_URL . 'assets/js/admin.js',
    array('jquery', 'jquery-ui-autocomplete'),
    CAA_VERSION,
    true
);

// Localize script with AJAX data
wp_localize_script('caa-admin', 'caaAdmin', array(
    'ajaxUrl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('caa_admin_nonce'),
    'selectedItems' => isset($inst_selected_items) ? $inst_selected_items : array(),
    'selectedInstanceId' => $selected_instance_id
));
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper caa-tabs">
        <a href="#general-settings" class="nav-tab nav-tab-active" data-tab="general-settings">
            <?php esc_html_e('General Settings', 'logo-collision'); ?>
        </a>
        <a href="#pro-version" class="nav-tab" data-tab="pro-version">
            <?php esc_html_e('Pro Version', 'logo-collision'); ?>
        </a>
    </nav>
    
    <!-- General Settings Tab -->
    <div id="general-settings" class="caa-tab-content caa-tab-active">
    <form method="post" action="">
        <?php wp_nonce_field('caa_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="caa_logo_id"><?php esc_html_e('Header Logo ID', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="caa_logo_id" 
                            name="caa_logo_id" 
                            value="<?php echo esc_attr($logo_id); ?>" 
                            class="regular-text"
                            placeholder="#site-logo or .logo"
                        />
                        <p class="description">
                            <?php esc_html_e('Enter the CSS selector for your header logo (e.g., #site-logo, .logo, or #header-logo).', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Global Animation Settings', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <table class="form-table" style="margin-top: 0;">
                            <tr>
                                <th scope="row" style="width: 200px;">
                                    <label for="caa_duration"><?php esc_html_e('Animation Duration', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        id="caa_duration" 
                                        name="caa_duration" 
                                        value="<?php echo esc_attr($duration); ?>" 
                                        class="small-text"
                                        min="0.1"
                                        max="10"
                                        step="0.1"
                                    />
                                    <span>s</span>
                                    <p class="description">
                                        <?php esc_html_e('Duration of the animation in seconds (0.1 - 2.0).', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_ease"><?php esc_html_e('Easing Type', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <select id="caa_ease" name="caa_ease">
                                        <option value="power1" <?php selected($ease, 'power1'); ?>>Power 1</option>
                                        <option value="power2" <?php selected($ease, 'power2'); ?>>Power 2</option>
                                        <option value="power3" <?php selected($ease, 'power3'); ?>>Power 3</option>
                                        <option value="power4" <?php selected($ease, 'power4'); ?>>Power 4</option>
                                        <option value="expo" <?php selected($ease, 'expo'); ?>>Expo</option>
                                        <option value="sine" <?php selected($ease, 'sine'); ?>>Sine</option>
                                        <option value="back" <?php selected($ease, 'back'); ?>>Back</option>
                                        <option value="elastic" <?php selected($ease, 'elastic'); ?>>Elastic</option>
                                        <option value="bounce" <?php selected($ease, 'bounce'); ?>>Bounce</option>
                                        <option value="none" <?php selected($ease, 'none'); ?>>None</option>
                                    </select>
                                    <p class="description">
                                        <?php esc_html_e('Easing function for the animation.', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_offset_start"><?php esc_html_e('Scroll Trigger Start Offset', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        id="caa_offset_start" 
                                        name="caa_offset_start" 
                                        value="<?php echo esc_attr($offset_start); ?>" 
                                        class="small-text"
                                        step="1"
                                    />
                                    <span>px</span>
                                    <p class="description">
                                        <?php esc_html_e('Offset in pixels for when the animation starts. Use negative values to trigger earlier, positive values to trigger later.', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_offset_end"><?php esc_html_e('Scroll Trigger End Offset', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        id="caa_offset_end" 
                                        name="caa_offset_end" 
                                        value="<?php echo esc_attr($offset_end); ?>" 
                                        class="small-text"
                                        step="1"
                                    />
                                    <span>px</span>
                                    <p class="description">
                                        <?php esc_html_e('Offset in pixels for when the animation ends. Use negative values to trigger earlier, positive values to trigger later.', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_selected_effect"><?php esc_html_e('Effect Selection', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Select Animation Effect', 'logo-collision'); ?></span>
                            </legend>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="1" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '1'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 1: Scale', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Scales down and hides the logo, then scales up and shows it.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="1" <?php echo $selected_effect === '1' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect1_scale_down"><?php esc_html_e('Scale Down Value', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect1_scale_down" 
                                                        name="caa_effect1_scale_down" 
                                                        value="<?php echo esc_attr($effect1_scale_down); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="1"
                                                        step="0.1"
                                                    />
                                                    <p class="description">
                                                        <?php esc_html_e('Scale value when hidden (0.0 - 1.0).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect1_origin_x"><?php esc_html_e('Transform Origin X', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect1_origin_x" 
                                                        name="caa_effect1_origin_x" 
                                                        value="<?php echo esc_attr($effect1_origin_x); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="500"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal transform origin (0% - 100%).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect1_origin_y"><?php esc_html_e('Transform Origin Y', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect1_origin_y" 
                                                        name="caa_effect1_origin_y" 
                                                        value="<?php echo esc_attr($effect1_origin_y); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="500"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Vertical transform origin (0% - 100%).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="2" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '2'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 2: Blur', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Applies blur effect and scales the logo.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="2" <?php echo $selected_effect === '2' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect2_blur_amount"><?php esc_html_e('Blur Amount', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect2_blur_amount" 
                                                        name="caa_effect2_blur_amount" 
                                                        value="<?php echo esc_attr($effect2_blur_amount); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="100"
                                                        step="0.5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Intensity of the blur effect (0 - 20px).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect2_blur_scale"><?php esc_html_e('Scale During Blur', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect2_blur_scale" 
                                                        name="caa_effect2_blur_scale" 
                                                        value="<?php echo esc_attr($effect2_blur_scale); ?>" 
                                                        class="small-text"
                                                        min="0.5"
                                                        max="1"
                                                        step="0.05"
                                                    />
                                                    <p class="description">
                                                        <?php esc_html_e('Scale value applied during blur (0.5 - 1.0).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect2_blur_duration"><?php esc_html_e('Blur Duration', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect2_blur_duration" 
                                                        name="caa_effect2_blur_duration" 
                                                        value="<?php echo esc_attr($effect2_blur_duration); ?>" 
                                                        class="small-text"
                                                        min="0.1"
                                                        max="5"
                                                        step="0.1"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Duration of the blur animation (0.1 - 1.0s).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="3" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '3'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 3: Slide Text', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Slides text up and down.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="3" <?php echo $selected_effect === '3' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <p class="description" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                                            <?php esc_html_e('This effect uses global animation settings only. No additional configuration is required.', 'logo-collision'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="4" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '4'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 4: Text Split', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Splits text into characters and scatters them.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="4" <?php echo $selected_effect === '4' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect4_text_x_range"><?php esc_html_e('Random X Range', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_text_x_range" 
                                                        name="caa_effect4_text_x_range" 
                                                        value="<?php echo esc_attr($effect4_text_x_range); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="1000"
                                                        step="5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Maximum horizontal displacement for characters (0 - 200px).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect4_text_y_range"><?php esc_html_e('Random Y Range', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_text_y_range" 
                                                        name="caa_effect4_text_y_range" 
                                                        value="<?php echo esc_attr($effect4_text_y_range); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="1000"
                                                        step="5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Maximum vertical displacement for characters (0 - 200px).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect4_stagger_amount"><?php esc_html_e('Stagger Amount', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_stagger_amount" 
                                                        name="caa_effect4_stagger_amount" 
                                                        value="<?php echo esc_attr($effect4_stagger_amount); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="2.5"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Delay between each character\'s animation (0 - 0.5s).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="5" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '5'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 5: Character Shuffle', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Shuffles characters before revealing the original text.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="5" <?php echo $selected_effect === '5' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect5_shuffle_iterations"><?php esc_html_e('Shuffle Iterations', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_shuffle_iterations" 
                                                        name="caa_effect5_shuffle_iterations" 
                                                        value="<?php echo esc_attr($effect5_shuffle_iterations); ?>" 
                                                        class="small-text"
                                                        min="1"
                                                        max="50"
                                                        step="1"
                                                    />
                                                    <p class="description">
                                                        <?php esc_html_e('Number of times characters shuffle before revealing (1 - 10).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect5_shuffle_duration"><?php esc_html_e('Shuffle Duration', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_shuffle_duration" 
                                                        name="caa_effect5_shuffle_duration" 
                                                        value="<?php echo esc_attr($effect5_shuffle_duration); ?>" 
                                                        class="small-text"
                                                        min="0.01"
                                                        max="0.5"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Duration of each shuffle iteration (0.01 - 0.1s).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect5_char_delay"><?php esc_html_e('Character Delay', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_char_delay" 
                                                        name="caa_effect5_char_delay" 
                                                        value="<?php echo esc_attr($effect5_char_delay); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="1.0"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Delay between each character\'s shuffle sequence (0 - 0.2s).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="6" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '6'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 6: Rotation', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Rotates and moves the logo simultaneously.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="6" <?php echo $selected_effect === '6' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect6_rotation"><?php esc_html_e('Rotation Angle', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_rotation" 
                                                        name="caa_effect6_rotation" 
                                                        value="<?php echo esc_attr($effect6_rotation); ?>" 
                                                        class="small-text"
                                                        min="-180"
                                                        max="900"
                                                        step="5"
                                                    />
                                                    <span>°</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Degrees of rotation (-180° - 180°).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_x_percent"><?php esc_html_e('X Percent Offset', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_x_percent" 
                                                        name="caa_effect6_x_percent" 
                                                        value="<?php echo esc_attr($effect6_x_percent); ?>" 
                                                        class="small-text"
                                                        min="-50"
                                                        max="250"
                                                        step="1"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal offset during rotation (-50% - 50%).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_origin_x"><?php esc_html_e('Transform Origin X', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_origin_x" 
                                                        name="caa_effect6_origin_x" 
                                                        value="<?php echo esc_attr($effect6_origin_x); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="500"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal pivot point (0% - 100%).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_origin_y"><?php esc_html_e('Transform Origin Y', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_origin_y" 
                                                        name="caa_effect6_origin_y" 
                                                        value="<?php echo esc_attr($effect6_origin_y); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="500"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Vertical pivot point (0% - 100%).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="caa-effect-option">
                                <label>
                                    <input 
                                        type="radio" 
                                        name="caa_selected_effect" 
                                        value="7" 
                                        class="caa-effect-radio"
                                        <?php checked($selected_effect, '7'); ?>
                                    />
                                    <strong><?php esc_html_e('Effect 7: Move Away', 'logo-collision'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Moves the logo horizontally off-screen.', 'logo-collision'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="7" <?php echo $selected_effect === '7' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect7_move_distance"><?php esc_html_e('Move Distance', 'logo-collision'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="text" 
                                                        id="caa_effect7_move_distance" 
                                                        name="caa_effect7_move_distance" 
                                                        value="<?php echo esc_attr($effect7_move_distance); ?>" 
                                                        class="regular-text"
                                                        placeholder="auto"
                                                    />
                                                    <p class="description">
                                                        <?php esc_html_e('Distance to move the logo (e.g., "100px" or "50%"). Leave empty for default behavior (moves by logo width).', 'logo-collision'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_included_elements"><?php esc_html_e('Include Elements', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <textarea 
                            id="caa_included_elements" 
                            name="caa_included_elements" 
                            rows="5" 
                            class="large-text code"
                            placeholder="#main-content, .entry-content, article"
                        ><?php echo esc_textarea($included_elements); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Enter CSS selectors (comma-separated) for elements that should trigger the animation. If left empty, the plugin will auto-detect common content areas.', 'logo-collision'); ?>
                            <br>
                            <?php esc_html_e('Example: #main-content, .entry-content, article, .post-content', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_excluded_elements"><?php esc_html_e('Excluded Elements', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <textarea 
                            id="caa_excluded_elements" 
                            name="caa_excluded_elements" 
                            rows="5" 
                            class="large-text code"
                            placeholder="#sidebar, .widget, .navigation"
                        ><?php echo esc_textarea($excluded_elements); ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Enter CSS selectors (comma-separated) for elements that should be excluded from collision detection. These elements will not trigger the animation.', 'logo-collision'); ?>
                            <br>
                            <?php esc_html_e('Example: #sidebar, .widget, .navigation, footer', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_global_offset"><?php esc_html_e('Global Offset', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <input 
                            type="number" 
                            id="caa_global_offset" 
                            name="caa_global_offset" 
                            value="<?php echo esc_attr($global_offset); ?>" 
                            class="small-text"
                            step="1"
                            placeholder="0"
                        />
                        <span>px</span>
                        <p class="description">
                            <?php esc_html_e('Global offset in pixels to adjust when the effect is triggered. Use positive values to trigger earlier, negative values to trigger later. This offset is applied to both the start and end positions of the ScrollTrigger.', 'logo-collision'); ?>
                            <br>
                            <?php esc_html_e('Example: -50 (triggers 50px later), +30 (triggers 30px earlier)', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_disable_mobile"><?php esc_html_e('Disable on Mobile', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                id="caa_disable_mobile" 
                                name="caa_disable_mobile" 
                                value="1"
                                <?php checked($disable_mobile, '1'); ?>
                            />
                            <?php esc_html_e('Disable effects on small screens', 'logo-collision'); ?>
                        </label>
                        <div class="caa-mobile-breakpoint-field" style="margin-top: 10px;">
                            <label for="caa_mobile_breakpoint">
                                <?php esc_html_e('Breakpoint:', 'logo-collision'); ?>
                                <input 
                                    type="number" 
                                    id="caa_mobile_breakpoint" 
                                    name="caa_mobile_breakpoint" 
                                    value="<?php echo esc_attr($mobile_breakpoint); ?>"
                                    min="0"
                                    max="10000"
                                    style="width: 80px;"
                                /> px
                            </label>
                        </div>
                        <p class="description">
                            <?php esc_html_e('When enabled, effects will be disabled on viewports smaller than the specified breakpoint width.', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_debug_mode"><?php esc_html_e('Debug Mode', 'logo-collision'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                id="caa_debug_mode" 
                                name="caa_debug_mode" 
                                value="1"
                                <?php checked($debug_mode, '1'); ?>
                            />
                            <?php esc_html_e('Enable debug console output', 'logo-collision'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, detailed debugging information will be logged to the browser console. Useful for troubleshooting and understanding how the plugin detects and processes elements.', 'logo-collision'); ?>
                        </p>
                    </td>
                </tr>
                
            </tbody>
        </table>
        
        <?php submit_button(__('Save Changes', 'logo-collision'), 'primary', 'caa_save_settings'); ?>
    </form>
    </div><!-- End General Settings Tab -->
    
    <!-- Pro Version Tab -->
    <div id="pro-version" class="caa-tab-content">
        <!-- Instance Selector Bar -->
        <div class="caa-instance-selector-bar">
            <label for="caa-instance-select"><?php esc_html_e('Instance:', 'logo-collision'); ?></label>
            <select id="caa-instance-select" class="caa-instance-dropdown">
                <?php foreach ($all_instances as $inst_id => $inst) : 
                    $inst_name = $plugin_instance->get_instance_name($inst_id, $inst);
                ?>
                    <option value="<?php echo esc_attr($inst_id); ?>" <?php selected($selected_instance_id, $inst_id); ?>>
                        <?php echo esc_html($inst_name); ?>
                        <?php if (empty($inst['enabled'])) : ?>(<?php esc_html_e('disabled', 'logo-collision'); ?>)<?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($can_add_instance) : ?>
                <a href="<?php echo esc_url(admin_url('options-general.php?page=logo-collision&instance_id=0#pro-version')); ?>" class="button button-secondary caa-new-instance-btn">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php esc_html_e('New Instance', 'logo-collision'); ?>
                </a>
            <?php endif; ?>
            <?php if ($selected_instance_id > 0 && $instance_count > 1) : ?>
                <form method="post" action="" style="display: inline;">
                    <?php wp_nonce_field('caa_instance_nonce'); ?>
                    <input type="hidden" name="caa_instance_id" value="<?php echo esc_attr($selected_instance_id); ?>" />
                    <button type="submit" name="caa_delete_instance" class="button button-link-delete caa-delete-instance-btn" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this instance?', 'logo-collision'); ?>');">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Delete', 'logo-collision'); ?>
                    </button>
                </form>
            <?php endif; ?>
            <span class="caa-instance-count"><?php
			/* translators: 1: Current instance count, 2: Maximum instance count */
			echo esc_html(sprintf(__('%1$d of %2$d instances', 'logo-collision'), $instance_count, CAA_MAX_INSTANCES)); ?></span>
        </div>
        
        <!-- Sub-tab Navigation -->
        <nav class="nav-tab-wrapper caa-sub-tabs">
            <a href="#pro-general" class="nav-tab nav-tab-active" data-subtab="pro-general">
                <?php esc_html_e('General Settings', 'logo-collision'); ?>
            </a>
            <a href="#pro-mappings" class="nav-tab" data-subtab="pro-mappings">
                <?php esc_html_e('Element Mappings', 'logo-collision'); ?>
            </a>
            <a href="#pro-filtering" class="nav-tab" data-subtab="pro-filtering">
                <?php esc_html_e('Page Filtering', 'logo-collision'); ?>
            </a>
        </nav>
        
        <!-- General Settings Sub-tab (for selected instance) -->
        <div id="pro-general" class="caa-sub-tab-content caa-sub-tab-active">
            <!-- Instance General Settings Form -->
            <form method="post" action="" class="caa-instance-editor">
                <?php wp_nonce_field('caa_instance_nonce'); ?>
                <input type="hidden" name="caa_instance_id" value="<?php echo esc_attr($selected_instance_id); ?>" />
                
                <div class="caa-pro-header">
                    <h2>
                        <?php 
                        if ($creating_new_instance) {
                            esc_html_e('Create New Instance', 'logo-collision');
                        } else {
                            /* translators: %s: Instance name */
                            printf(esc_html__('Instance Settings: %s', 'logo-collision'), esc_html($plugin_instance->get_instance_name($selected_instance_id, $selected_instance)));
                        }
                        ?>
                    </h2>
                    <p class="description">
                        <?php esc_html_e('Configure this instance with its own logo, effects, and settings.', 'logo-collision'); ?>
                    </p>
                </div>
                    
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="caa_instance_enabled"><?php esc_html_e('Enabled', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox" id="caa_instance_enabled" name="caa_instance_enabled" value="1" <?php checked(!empty($selected_instance['enabled'])); ?> />
                                        <?php esc_html_e('Enable this instance', 'logo-collision'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_instance_logo_id"><?php esc_html_e('Logo Selector', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="caa_instance_logo_id" name="caa_instance_logo_id" value="<?php echo esc_attr($selected_instance['logo_id']); ?>" class="regular-text" placeholder="#site-logo or .logo" />
                                    <p class="description"><?php esc_html_e('CSS selector for the element to animate (e.g., #site-logo, .custom-logo).', 'logo-collision'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Default Effect', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <?php
                                    $inst_effect = isset($selected_instance['selected_effect']) ? $selected_instance['selected_effect'] : '1';
                                    ?>
                                    <fieldset>
                                        <label><input type="radio" name="caa_instance_effect" value="1" <?php checked($inst_effect, '1'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 1: Scale', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="2" <?php checked($inst_effect, '2'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 2: Blur', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="3" <?php checked($inst_effect, '3'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 3: Slide Text', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="4" <?php checked($inst_effect, '4'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 4: Text Split', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="5" <?php checked($inst_effect, '5'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 5: Character Shuffle', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="6" <?php checked($inst_effect, '6'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 6: Rotation', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_effect" value="7" <?php checked($inst_effect, '7'); ?> class="caa-instance-effect-radio" /> <?php esc_html_e('Effect 7: Move Away', 'logo-collision'); ?></label>
                                    </fieldset>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_instance_included"><?php esc_html_e('Include Elements', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <textarea id="caa_instance_included" name="caa_instance_included" class="large-text" rows="2" placeholder=".content-section, .hero-section"><?php echo esc_textarea($selected_instance['included_elements']); ?></textarea>
                                    <p class="description"><?php esc_html_e('Comma-separated CSS selectors for elements that should trigger the animation.', 'logo-collision'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_instance_excluded"><?php esc_html_e('Exclude Elements', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <textarea id="caa_instance_excluded" name="caa_instance_excluded" class="large-text" rows="2" placeholder=".no-animation, .skip-collision"><?php echo esc_textarea($selected_instance['excluded_elements']); ?></textarea>
                                    <p class="description"><?php esc_html_e('Comma-separated CSS selectors for elements to exclude from triggering.', 'logo-collision'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <h3><?php esc_html_e('Animation Settings', 'logo-collision'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label for="caa_instance_duration"><?php esc_html_e('Duration', 'logo-collision'); ?></label></th>
                                <td>
                                    <input type="number" id="caa_instance_duration" name="caa_instance_duration" value="<?php echo esc_attr($selected_instance['duration']); ?>" min="0.1" max="15" step="0.1" class="small-text" /> s
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="caa_instance_ease"><?php esc_html_e('Easing', 'logo-collision'); ?></label></th>
                                <td>
                                    <select id="caa_instance_ease" name="caa_instance_ease">
                                        <?php
                                        $eases = array('power1', 'power2', 'power3', 'power4', 'expo', 'sine', 'back', 'elastic', 'bounce', 'none');
                                        foreach ($eases as $e) {
                                            printf('<option value="%s" %s>%s</option>', esc_attr($e), selected($selected_instance['ease'], $e, false), esc_html(ucfirst($e)));
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="caa_instance_offset_start"><?php esc_html_e('Start Offset', 'logo-collision'); ?></label></th>
                                <td>
                                    <input type="number" id="caa_instance_offset_start" name="caa_instance_offset_start" value="<?php echo esc_attr($selected_instance['offset_start']); ?>" step="1" class="small-text" /> px
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="caa_instance_offset_end"><?php esc_html_e('End Offset', 'logo-collision'); ?></label></th>
                                <td>
                                    <input type="number" id="caa_instance_offset_end" name="caa_instance_offset_end" value="<?php echo esc_attr($selected_instance['offset_end']); ?>" step="1" class="small-text" /> px
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="caa_instance_global_offset"><?php esc_html_e('Global Offset', 'logo-collision'); ?></label></th>
                                <td>
                                    <input type="number" id="caa_instance_global_offset" name="caa_instance_global_offset" value="<?php echo esc_attr($selected_instance['global_offset']); ?>" step="1" class="small-text" /> px
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Effect-specific settings accordions -->
                    <div class="caa-instance-effect-accordion" data-effect="1" <?php echo $inst_effect === '1' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 1: Scale Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Scale Down', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect1_scale_down" value="<?php echo esc_attr($selected_instance['effect1_scale_down']); ?>" min="0" max="1" step="0.1" class="small-text" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Transform Origin', 'logo-collision'); ?></label></th>
                                    <td>
                                        X: <input type="number" name="caa_instance_effect1_origin_x" value="<?php echo esc_attr($selected_instance['effect1_origin_x']); ?>" min="0" max="500" step="5" class="small-text" />%
                                        Y: <input type="number" name="caa_instance_effect1_origin_y" value="<?php echo esc_attr($selected_instance['effect1_origin_y']); ?>" min="0" max="500" step="5" class="small-text" />%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="2" <?php echo $inst_effect === '2' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 2: Blur Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Blur Amount', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect2_blur_amount" value="<?php echo esc_attr($selected_instance['effect2_blur_amount']); ?>" min="0" max="100" step="0.5" class="small-text" /> px</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Blur Scale', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect2_blur_scale" value="<?php echo esc_attr($selected_instance['effect2_blur_scale']); ?>" min="0.5" max="1" step="0.05" class="small-text" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Blur Duration', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect2_blur_duration" value="<?php echo esc_attr($selected_instance['effect2_blur_duration']); ?>" min="0.1" max="5" step="0.1" class="small-text" /> s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="3" <?php echo $inst_effect === '3' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 3: Slide Text', 'logo-collision'); ?></h3>
                        <p class="description"><?php esc_html_e('This effect uses only the global animation settings above.', 'logo-collision'); ?></p>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="4" <?php echo $inst_effect === '4' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 4: Text Split Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('X Range', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect4_text_x_range" value="<?php echo esc_attr($selected_instance['effect4_text_x_range']); ?>" min="0" max="1000" step="5" class="small-text" /> px</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Y Range', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect4_text_y_range" value="<?php echo esc_attr($selected_instance['effect4_text_y_range']); ?>" min="0" max="1000" step="5" class="small-text" /> px</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Stagger', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect4_stagger_amount" value="<?php echo esc_attr($selected_instance['effect4_stagger_amount']); ?>" min="0" max="2.5" step="0.01" class="small-text" /> s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="5" <?php echo $inst_effect === '5' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 5: Character Shuffle Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Iterations', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect5_shuffle_iterations" value="<?php echo esc_attr($selected_instance['effect5_shuffle_iterations']); ?>" min="1" max="50" step="1" class="small-text" /></td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Shuffle Duration', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect5_shuffle_duration" value="<?php echo esc_attr($selected_instance['effect5_shuffle_duration']); ?>" min="0.01" max="0.5" step="0.01" class="small-text" /> s</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Char Delay', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect5_char_delay" value="<?php echo esc_attr($selected_instance['effect5_char_delay']); ?>" min="0" max="1.0" step="0.01" class="small-text" /> s</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="6" <?php echo $inst_effect === '6' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 6: Rotation Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Rotation', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect6_rotation" value="<?php echo esc_attr($selected_instance['effect6_rotation']); ?>" min="-180" max="900" step="5" class="small-text" /> &deg;</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('X Percent', 'logo-collision'); ?></label></th>
                                    <td><input type="number" name="caa_instance_effect6_x_percent" value="<?php echo esc_attr($selected_instance['effect6_x_percent']); ?>" min="-50" max="250" step="1" class="small-text" /> %</td>
                                </tr>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Transform Origin', 'logo-collision'); ?></label></th>
                                    <td>
                                        X: <input type="number" name="caa_instance_effect6_origin_x" value="<?php echo esc_attr($selected_instance['effect6_origin_x']); ?>" min="0" max="500" step="5" class="small-text" />%
                                        Y: <input type="number" name="caa_instance_effect6_origin_y" value="<?php echo esc_attr($selected_instance['effect6_origin_y']); ?>" min="0" max="500" step="5" class="small-text" />%
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="caa-instance-effect-accordion" data-effect="7" <?php echo $inst_effect === '7' ? 'style="display:block;"' : ''; ?>>
                        <h3><?php esc_html_e('Effect 7: Move Away Settings', 'logo-collision'); ?></h3>
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><label><?php esc_html_e('Move Distance', 'logo-collision'); ?></label></th>
                                    <td>
                                        <input type="text" name="caa_instance_effect7_move_distance" value="<?php echo esc_attr($selected_instance['effect7_move_distance']); ?>" class="regular-text" placeholder="auto (e.g., 100px or 50%)" />
                                        <p class="description"><?php esc_html_e('Leave empty for auto (moves element off-screen).', 'logo-collision'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h3><?php esc_html_e('Page Filtering', 'logo-collision'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Enable Filtering', 'logo-collision'); ?></label></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="caa_instance_enable_filtering" value="1" <?php checked($selected_instance['enable_filtering'], '1'); ?> id="caa_instance_enable_filtering" />
                                        <?php esc_html_e('Enable page filtering for this instance', 'logo-collision'); ?>
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div id="caa-instance-filtering-options" style="<?php echo $selected_instance['enable_filtering'] === '1' ? '' : 'display:none;'; ?>">
                        <table class="form-table" role="presentation">
                            <tbody>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Filter Mode', 'logo-collision'); ?></th>
                                    <td>
                                        <label><input type="radio" name="caa_instance_filter_mode" value="include" <?php checked($selected_instance['filter_mode'], 'include'); ?> /> <?php esc_html_e('Include only selected', 'logo-collision'); ?></label><br>
                                        <label><input type="radio" name="caa_instance_filter_mode" value="exclude" <?php checked($selected_instance['filter_mode'], 'exclude'); ?> /> <?php esc_html_e('Exclude selected', 'logo-collision'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Pages', 'logo-collision'); ?></th>
                                    <td>
                                        <label><input type="checkbox" name="caa_instance_include_pages" value="1" <?php checked($selected_instance['include_pages'], '1'); ?> /> <?php esc_html_e('All pages', 'logo-collision'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Posts', 'logo-collision'); ?></th>
                                    <td>
                                        <label><input type="checkbox" name="caa_instance_include_posts" value="1" <?php checked($selected_instance['include_posts'], '1'); ?> /> <?php esc_html_e('All posts', 'logo-collision'); ?></label>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php esc_html_e('Post Types', 'logo-collision'); ?></th>
                                    <td>
                                        <?php
                                        $inst_post_types = isset($selected_instance['selected_post_types']) ? $selected_instance['selected_post_types'] : array();
                                        foreach ($all_post_types as $pt_slug => $pt_obj) :
                                            if (in_array($pt_slug, array('post', 'page', 'attachment'), true)) continue;
                                        ?>
                                            <label><input type="checkbox" name="caa_instance_post_types[]" value="<?php echo esc_attr($pt_slug); ?>" <?php checked(in_array($pt_slug, $inst_post_types, true)); ?> /> <?php echo esc_html($pt_obj->labels->name); ?></label><br>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <h3><?php esc_html_e('Debug', 'logo-collision'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row"><label><?php esc_html_e('Debug Mode', 'logo-collision'); ?></label></th>
                                <td>
                                    <label><input type="checkbox" name="caa_instance_debug" value="1" <?php checked($selected_instance['debug_mode'], '1'); ?> /> <?php esc_html_e('Enable debug console output', 'logo-collision'); ?></label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <?php submit_button(__('Save Instance', 'logo-collision'), 'primary', 'caa_save_instance'); ?>
                </form>
        </div><!-- End General Settings Sub-tab -->
        
        <!-- Mappings Sub-tab -->
        <div id="pro-mappings" class="caa-sub-tab-content">
            <form method="post" action="">
                <?php wp_nonce_field('caa_instance_mappings_nonce'); ?>
                <input type="hidden" name="caa_instance_id" value="<?php echo esc_attr($selected_instance_id); ?>" />
                
                <div class="caa-pro-header">
                    <h2><?php esc_html_e('Element Effect Mappings', 'logo-collision'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Map specific elements to different effects for this instance. When the logo collides with these elements, the mapped effect will be used instead of the default.', 'logo-collision'); ?>
                    </p>
                </div>
                
                <div class="caa-mappings-container">
                    <div class="caa-mappings-header">
                        <span class="caa-mapping-col-selector"><?php esc_html_e('Element Selector', 'logo-collision'); ?></span>
                        <span class="caa-mapping-col-effect"><?php esc_html_e('Effect', 'logo-collision'); ?></span>
                        <span class="caa-mapping-col-override"><?php esc_html_e('Override', 'logo-collision'); ?></span>
                        <span class="caa-mapping-col-actions"><?php esc_html_e('Actions', 'logo-collision'); ?></span>
                    </div>
                    
                    <div id="caa-mappings-list">
                        <?php
                        // Get mappings from the selected instance
                        $effect_mappings = isset($selected_instance['effect_mappings']) ? $selected_instance['effect_mappings'] : array();
                        if (empty($effect_mappings)) {
                            $effect_mappings = array(array('selector' => '', 'effect' => '1', 'override_enabled' => false));
                        }
                        foreach ($effect_mappings as $index => $mapping) :
                            $selector_value = isset($mapping['selector']) ? $mapping['selector'] : '';
                            $effect_value = isset($mapping['effect']) ? $mapping['effect'] : '1';
                            $override_enabled = isset($mapping['override_enabled']) && $mapping['override_enabled'];
                            $settings = isset($mapping['settings']) ? $mapping['settings'] : array();
                            
                            // Get settings values with defaults
                            $s_duration = isset($settings['duration']) ? $settings['duration'] : '0.6';
                            $s_ease = isset($settings['ease']) ? $settings['ease'] : 'power4';
                            $s_offset_start = isset($settings['offset_start']) ? $settings['offset_start'] : '30';
                            $s_offset_end = isset($settings['offset_end']) ? $settings['offset_end'] : '10';
                            
                            // Effect 1 settings
                            $s_effect1_scale_down = isset($settings['effect1_scale_down']) ? $settings['effect1_scale_down'] : '0';
                            $s_effect1_origin_x = isset($settings['effect1_origin_x']) ? $settings['effect1_origin_x'] : '0';
                            $s_effect1_origin_y = isset($settings['effect1_origin_y']) ? $settings['effect1_origin_y'] : '50';
                            
                            // Effect 2 settings
                            $s_effect2_blur_amount = isset($settings['effect2_blur_amount']) ? $settings['effect2_blur_amount'] : '5';
                            $s_effect2_blur_scale = isset($settings['effect2_blur_scale']) ? $settings['effect2_blur_scale'] : '0.9';
                            $s_effect2_blur_duration = isset($settings['effect2_blur_duration']) ? $settings['effect2_blur_duration'] : '0.2';
                            
                            // Effect 4 settings
                            $s_effect4_text_x_range = isset($settings['effect4_text_x_range']) ? $settings['effect4_text_x_range'] : '50';
                            $s_effect4_text_y_range = isset($settings['effect4_text_y_range']) ? $settings['effect4_text_y_range'] : '40';
                            $s_effect4_stagger_amount = isset($settings['effect4_stagger_amount']) ? $settings['effect4_stagger_amount'] : '0.03';
                            
                            // Effect 5 settings
                            $s_effect5_shuffle_iterations = isset($settings['effect5_shuffle_iterations']) ? $settings['effect5_shuffle_iterations'] : '2';
                            $s_effect5_shuffle_duration = isset($settings['effect5_shuffle_duration']) ? $settings['effect5_shuffle_duration'] : '0.03';
                            $s_effect5_char_delay = isset($settings['effect5_char_delay']) ? $settings['effect5_char_delay'] : '0.03';
                            
                            // Effect 6 settings
                            $s_effect6_rotation = isset($settings['effect6_rotation']) ? $settings['effect6_rotation'] : '-90';
                            $s_effect6_x_percent = isset($settings['effect6_x_percent']) ? $settings['effect6_x_percent'] : '-5';
                            $s_effect6_origin_x = isset($settings['effect6_origin_x']) ? $settings['effect6_origin_x'] : '0';
                            $s_effect6_origin_y = isset($settings['effect6_origin_y']) ? $settings['effect6_origin_y'] : '100';
                            
                            // Effect 7 settings
                            $s_effect7_move_distance = isset($settings['effect7_move_distance']) ? $settings['effect7_move_distance'] : '';
                        ?>
                        <div class="caa-mapping-row-wrapper">
                            <div class="caa-mapping-row">
                                <div class="caa-mapping-col-selector">
                                    <input 
                                        type="text" 
                                        name="caa_mappings[<?php echo esc_attr($index); ?>][selector]" 
                                        value="<?php echo esc_attr($selector_value); ?>" 
                                        class="regular-text caa-mapping-selector"
                                        placeholder="#element-id or .class-name"
                                    />
                                </div>
                                <div class="caa-mapping-col-effect">
                                    <select name="caa_mappings[<?php echo esc_attr($index); ?>][effect]" class="caa-mapping-effect-select">
                                        <option value="1" <?php selected($effect_value, '1'); ?>><?php esc_html_e('Effect 1: Scale', 'logo-collision'); ?></option>
                                        <option value="2" <?php selected($effect_value, '2'); ?>><?php esc_html_e('Effect 2: Blur', 'logo-collision'); ?></option>
                                        <option value="3" <?php selected($effect_value, '3'); ?>><?php esc_html_e('Effect 3: Slide Text', 'logo-collision'); ?></option>
                                        <option value="4" <?php selected($effect_value, '4'); ?>><?php esc_html_e('Effect 4: Text Split', 'logo-collision'); ?></option>
                                        <option value="5" <?php selected($effect_value, '5'); ?>><?php esc_html_e('Effect 5: Character Shuffle', 'logo-collision'); ?></option>
                                        <option value="6" <?php selected($effect_value, '6'); ?>><?php esc_html_e('Effect 6: Rotation', 'logo-collision'); ?></option>
                                        <option value="7" <?php selected($effect_value, '7'); ?>><?php esc_html_e('Effect 7: Move Away', 'logo-collision'); ?></option>
                                    </select>
                                </div>
                                <div class="caa-mapping-col-override">
                                    <label class="caa-override-checkbox-label">
                                        <input 
                                            type="checkbox" 
                                            name="caa_mappings[<?php echo esc_attr($index); ?>][override_enabled]" 
                                            value="1"
                                            class="caa-override-checkbox"
                                            <?php checked($override_enabled); ?>
                                        />
                                        <span class="dashicons dashicons-admin-generic"></span>
                                    </label>
                                </div>
                                <div class="caa-mapping-col-actions">
                                    <button type="button" class="button caa-remove-mapping" title="<?php esc_attr_e('Remove Mapping', 'logo-collision'); ?>">
                                        <span class="dashicons dashicons-trash"></span>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Collapsible Settings Panel -->
                            <div class="caa-mapping-settings-panel" data-effect="<?php echo esc_attr($effect_value); ?>" <?php echo $override_enabled ? 'style="display: block;"' : ''; ?>>
                                <div class="caa-mapping-settings-content">
                                    <!-- Global Animation Settings -->
                                    <div class="caa-settings-section">
                                        <h4><?php esc_html_e('Animation Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Duration', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][duration]" value="<?php echo esc_attr($s_duration); ?>" min="0.1" max="10" step="0.1" class="small-text" />
                                                <span>s</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Ease', 'logo-collision'); ?></label>
                                                <select name="caa_mappings[<?php echo esc_attr($index); ?>][settings][ease]">
                                                    <option value="power1" <?php selected($s_ease, 'power1'); ?>>Power 1</option>
                                                    <option value="power2" <?php selected($s_ease, 'power2'); ?>>Power 2</option>
                                                    <option value="power3" <?php selected($s_ease, 'power3'); ?>>Power 3</option>
                                                    <option value="power4" <?php selected($s_ease, 'power4'); ?>>Power 4</option>
                                                    <option value="expo" <?php selected($s_ease, 'expo'); ?>>Expo</option>
                                                    <option value="sine" <?php selected($s_ease, 'sine'); ?>>Sine</option>
                                                    <option value="back" <?php selected($s_ease, 'back'); ?>>Back</option>
                                                    <option value="elastic" <?php selected($s_ease, 'elastic'); ?>>Elastic</option>
                                                    <option value="bounce" <?php selected($s_ease, 'bounce'); ?>>Bounce</option>
                                                    <option value="none" <?php selected($s_ease, 'none'); ?>>None</option>
                                                </select>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Start Offset', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][offset_start]" value="<?php echo esc_attr($s_offset_start); ?>" step="1" class="small-text" />
                                                <span>px</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('End Offset', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][offset_end]" value="<?php echo esc_attr($s_offset_end); ?>" step="1" class="small-text" />
                                                <span>px</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 1: Scale Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-1" <?php echo $effect_value === '1' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Scale Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Scale Down', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect1_scale_down]" value="<?php echo esc_attr($s_effect1_scale_down); ?>" min="0" max="1" step="0.1" class="small-text" />
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Origin X', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect1_origin_x]" value="<?php echo esc_attr($s_effect1_origin_x); ?>" min="0" max="500" step="5" class="small-text" />
                                                <span>%</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Origin Y', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect1_origin_y]" value="<?php echo esc_attr($s_effect1_origin_y); ?>" min="0" max="500" step="5" class="small-text" />
                                                <span>%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 2: Blur Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-2" <?php echo $effect_value === '2' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Blur Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Blur Amount', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect2_blur_amount]" value="<?php echo esc_attr($s_effect2_blur_amount); ?>" min="0" max="100" step="0.5" class="small-text" />
                                                <span>px</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Scale', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect2_blur_scale]" value="<?php echo esc_attr($s_effect2_blur_scale); ?>" min="0.5" max="1" step="0.05" class="small-text" />
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Duration', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect2_blur_duration]" value="<?php echo esc_attr($s_effect2_blur_duration); ?>" min="0.1" max="5" step="0.1" class="small-text" />
                                                <span>s</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 3: Slide Text (no specific settings) -->
                                    <div class="caa-effect-settings caa-effect-settings-3" <?php echo $effect_value === '3' ? 'style="display: block;"' : ''; ?>>
                                        <p class="description"><?php esc_html_e('This effect uses only the animation settings above.', 'logo-collision'); ?></p>
                                    </div>
                                    
                                    <!-- Effect 4: Text Split Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-4" <?php echo $effect_value === '4' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Text Split Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('X Range', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect4_text_x_range]" value="<?php echo esc_attr($s_effect4_text_x_range); ?>" min="0" max="1000" step="5" class="small-text" />
                                                <span>px</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Y Range', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect4_text_y_range]" value="<?php echo esc_attr($s_effect4_text_y_range); ?>" min="0" max="1000" step="5" class="small-text" />
                                                <span>px</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Stagger', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect4_stagger_amount]" value="<?php echo esc_attr($s_effect4_stagger_amount); ?>" min="0" max="2.5" step="0.01" class="small-text" />
                                                <span>s</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 5: Character Shuffle Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-5" <?php echo $effect_value === '5' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Character Shuffle Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Iterations', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect5_shuffle_iterations]" value="<?php echo esc_attr($s_effect5_shuffle_iterations); ?>" min="1" max="50" step="1" class="small-text" />
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Shuffle Duration', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect5_shuffle_duration]" value="<?php echo esc_attr($s_effect5_shuffle_duration); ?>" min="0.01" max="0.5" step="0.01" class="small-text" />
                                                <span>s</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Char Delay', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect5_char_delay]" value="<?php echo esc_attr($s_effect5_char_delay); ?>" min="0" max="1.0" step="0.01" class="small-text" />
                                                <span>s</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 6: Rotation Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-6" <?php echo $effect_value === '6' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Rotation Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Rotation', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect6_rotation]" value="<?php echo esc_attr($s_effect6_rotation); ?>" min="-180" max="900" step="5" class="small-text" />
                                                <span>°</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('X Percent', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect6_x_percent]" value="<?php echo esc_attr($s_effect6_x_percent); ?>" min="-50" max="250" step="1" class="small-text" />
                                                <span>%</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Origin X', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect6_origin_x]" value="<?php echo esc_attr($s_effect6_origin_x); ?>" min="0" max="500" step="5" class="small-text" />
                                                <span>%</span>
                                            </div>
                                            <div class="caa-setting-field">
                                                <label><?php esc_html_e('Origin Y', 'logo-collision'); ?></label>
                                                <input type="number" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect6_origin_y]" value="<?php echo esc_attr($s_effect6_origin_y); ?>" min="0" max="500" step="5" class="small-text" />
                                                <span>%</span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Effect 7: Move Away Settings -->
                                    <div class="caa-effect-settings caa-effect-settings-7" <?php echo $effect_value === '7' ? 'style="display: block;"' : ''; ?>>
                                        <h4><?php esc_html_e('Move Away Settings', 'logo-collision'); ?></h4>
                                        <div class="caa-settings-grid">
                                            <div class="caa-setting-field caa-setting-field-wide">
                                                <label><?php esc_html_e('Move Distance', 'logo-collision'); ?></label>
                                                <input type="text" name="caa_mappings[<?php echo esc_attr($index); ?>][settings][effect7_move_distance]" value="<?php echo esc_attr($s_effect7_move_distance); ?>" class="regular-text" placeholder="auto (e.g., 100px or 50%)" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="caa-mappings-footer">
                        <button type="button" id="caa-add-mapping" class="button button-secondary">
                            <span class="dashicons dashicons-plus-alt2"></span>
                            <?php esc_html_e('Add New Mapping', 'logo-collision'); ?>
                        </button>
                    </div>
                </div>
                
                <?php submit_button(__('Save Mappings', 'logo-collision'), 'primary', 'caa_save_instance_mappings'); ?>
            </form>
            
            <div class="caa-info-box" style="margin-top: 30px;">
                <h2><?php esc_html_e('How Element Mappings Work', 'logo-collision'); ?></h2>
                <p>
                    <?php esc_html_e('Element mappings allow you to apply different effects to specific sections of your page.', 'logo-collision'); ?>
                </p>
                <p>
                    <?php esc_html_e('For example, you might want:', 'logo-collision'); ?>
                </p>
                <ul>
                    <li><?php esc_html_e('The logo to scale down when passing your hero section', 'logo-collision'); ?></li>
                    <li><?php esc_html_e('The logo to blur when passing your portfolio section', 'logo-collision'); ?></li>
                    <li><?php esc_html_e('The logo to rotate when passing your testimonials', 'logo-collision'); ?></li>
                </ul>
                <p>
                    <?php esc_html_e('Enter a CSS selector (ID or class) and choose which effect to apply.', 'logo-collision'); ?>
                </p>
            </div>
        </div><!-- End Mappings Sub-tab -->
        
        <!-- Filtering Sub-tab -->
        <div id="pro-filtering" class="caa-sub-tab-content">
            <?php
            // Get filtering data from selected instance
            $inst_enable_filtering = isset($selected_instance['enable_filtering']) ? $selected_instance['enable_filtering'] : '0';
            $inst_filter_mode = isset($selected_instance['filter_mode']) ? $selected_instance['filter_mode'] : 'include';
            $inst_selected_post_types = isset($selected_instance['selected_post_types']) ? $selected_instance['selected_post_types'] : array();
            $inst_include_pages = isset($selected_instance['include_pages']) ? $selected_instance['include_pages'] : '0';
            $inst_include_posts = isset($selected_instance['include_posts']) ? $selected_instance['include_posts'] : '0';
            $inst_selected_items = isset($selected_instance['selected_items']) ? $selected_instance['selected_items'] : array();
            ?>
            <form method="post" action="" id="caa-filtering-form">
                <?php wp_nonce_field('caa_instance_filtering_nonce'); ?>
                <input type="hidden" name="caa_instance_id" value="<?php echo esc_attr($selected_instance_id); ?>" />
                
                <div class="caa-pro-header">
                    <h2><?php esc_html_e('Page Filtering', 'logo-collision'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Control where this instance runs. Choose to include or exclude specific post types, pages, posts, or individual items.', 'logo-collision'); ?>
                    </p>
                </div>
                
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="caa_instance_enable_filtering"><?php esc_html_e('Enable Filtering', 'logo-collision'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input 
                                        type="checkbox" 
                                        id="caa_instance_enable_filtering" 
                                        name="caa_instance_enable_filtering" 
                                        value="1"
                                        <?php checked($inst_enable_filtering, '1'); ?>
                                    />
                                    <?php esc_html_e('Enable page filtering for this instance', 'logo-collision'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When disabled, this instance runs on all pages. When enabled, you can specify where it should run.', 'logo-collision'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <div id="caa-filtering-options" style="<?php echo $inst_enable_filtering === '1' ? '' : 'display: none;'; ?>">
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label><?php esc_html_e('Filter Mode', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input 
                                                type="radio" 
                                                name="caa_instance_filter_mode" 
                                                value="include" 
                                                <?php checked($inst_filter_mode, 'include'); ?>
                                            />
                                            <strong><?php esc_html_e('Include Mode', 'logo-collision'); ?></strong>
                                            <span class="description"><?php esc_html_e(' - Run instance only on selected pages/post types/items', 'logo-collision'); ?></span>
                                        </label>
                                        <br>
                                        <label>
                                            <input 
                                                type="radio" 
                                                name="caa_instance_filter_mode" 
                                                value="exclude" 
                                                <?php checked($inst_filter_mode, 'exclude'); ?>
                                            />
                                            <strong><?php esc_html_e('Exclude Mode', 'logo-collision'); ?></strong>
                                            <span class="description"><?php esc_html_e(' - Run instance everywhere except selected pages/post types/items', 'logo-collision'); ?></span>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label id="caa-post-types-label"><?php esc_html_e('Post Types', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <fieldset style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                        <?php foreach ($all_post_types as $post_type => $post_type_obj) : ?>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input 
                                                    type="checkbox" 
                                                    name="caa_instance_post_types[]" 
                                                    value="<?php echo esc_attr($post_type); ?>"
                                                    <?php checked(in_array($post_type, $inst_selected_post_types, true)); ?>
                                                />
                                                <?php echo esc_html($post_type_obj->label); ?> (<code><?php echo esc_html($post_type); ?></code>)
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <p class="description" id="caa-post-types-desc">
                                        <?php esc_html_e('Select post types to include.', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label id="caa-pages-label"><?php esc_html_e('Pages', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input 
                                            type="checkbox" 
                                            name="caa_instance_include_pages" 
                                            value="1"
                                            <?php checked($inst_include_pages, '1'); ?>
                                        />
                                        <span id="caa-pages-text"><?php esc_html_e('Include all pages', 'logo-collision'); ?></span>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label id="caa-posts-label"><?php esc_html_e('Posts', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input 
                                            type="checkbox" 
                                            name="caa_instance_include_posts" 
                                            value="1"
                                            <?php checked($inst_include_posts, '1'); ?>
                                        />
                                        <span id="caa-posts-text"><?php esc_html_e('Include all posts', 'logo-collision'); ?></span>
                                    </label>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label id="caa-items-label"><?php esc_html_e('Individual Items', 'logo-collision'); ?></label>
                                </th>
                                <td>
                                    <div id="caa-items-autocomplete-container">
                                        <input 
                                            type="text" 
                                            id="caa-items-search" 
                                            class="regular-text"
                                            placeholder="<?php esc_attr_e('Search for posts or pages...', 'logo-collision'); ?>"
                                        />
                                    </div>
                                    <div id="caa-selected-items" style="margin-top: 10px;">
                                        <?php
                                        if (!empty($inst_selected_items)) {
                                            foreach ($inst_selected_items as $item_id) {
                                                $post = get_post($item_id);
                                                if ($post) {
                                                    $post_type_obj = get_post_type_object($post->post_type);
                                                    $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
                                                    echo '<span class="caa-item-tag" data-id="' . esc_attr($item_id) . '">';
                                                    echo esc_html($post->post_title) . ' (' . esc_html($post_type_label) . ' #' . esc_html($item_id) . ')';
                                                    echo ' <span class="caa-remove-item" style="cursor: pointer; color: #d63638;">×</span>';
                                                    echo '<input type="hidden" name="caa_instance_selected_items[]" value="' . esc_attr($item_id) . '" />';
                                                    echo '</span> ';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <p class="description" id="caa-items-desc">
                                        <?php esc_html_e('Search and select individual posts or pages to include.', 'logo-collision'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <?php submit_button(__('Save Filtering Settings', 'logo-collision'), 'primary', 'caa_save_instance_filtering'); ?>
            </form>
        </div><!-- End Filtering Sub-tab -->
    </div><!-- End Pro Version Tab -->
    
    <div class="caa-info-box">
        <h2><?php esc_html_e('How It Works', 'logo-collision'); ?></h2>
        <p>
            <?php esc_html_e('This plugin detects when your header logo would collide with scrolling content and applies the selected animation effect to move it out of the way.', 'logo-collision'); ?>
        </p>
        <p>
            <?php esc_html_e('The plugin automatically detects common WordPress content areas such as:', 'logo-collision'); ?>
        </p>
        <ul>
            <li><code>.entry-content</code></li>
            <li><code>main</code></li>
            <li><code>.content</code></li>
            <li><code>.post-content</code></li>
            <li><code>article</code></li>
        </ul>
        <p>
            <?php esc_html_e('Use the "Include Elements" field to specify which elements should trigger the animation. If left empty, the plugin will auto-detect common content areas.', 'logo-collision'); ?>
        </p>
        <p>
            <?php esc_html_e('Use the "Excluded Elements" field to prevent specific elements from triggering the animation.', 'logo-collision'); ?>
        </p>
    </div>
</div>


