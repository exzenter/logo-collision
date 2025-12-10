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

// Handle form submission
if (isset($_POST['submit']) && check_admin_referer('caa_settings_nonce')) {
    update_option('caa_logo_id', sanitize_text_field($_POST['caa_logo_id']));
    update_option('caa_selected_effect', sanitize_text_field($_POST['caa_selected_effect']));
    update_option('caa_included_elements', sanitize_textarea_field($_POST['caa_included_elements']));
    update_option('caa_excluded_elements', sanitize_textarea_field($_POST['caa_excluded_elements']));
    update_option('caa_global_offset', sanitize_text_field($_POST['caa_global_offset']));
    update_option('caa_debug_mode', isset($_POST['caa_debug_mode']) ? '1' : '0');
    
    // Global animation settings
    update_option('caa_duration', sanitize_text_field($_POST['caa_duration']));
    update_option('caa_ease', sanitize_text_field($_POST['caa_ease']));
    update_option('caa_offset_start', sanitize_text_field($_POST['caa_offset_start']));
    update_option('caa_offset_end', sanitize_text_field($_POST['caa_offset_end']));
    
    // Effect 1: Scale
    update_option('caa_effect1_scale_down', sanitize_text_field($_POST['caa_effect1_scale_down']));
    update_option('caa_effect1_origin_x', sanitize_text_field($_POST['caa_effect1_origin_x']));
    update_option('caa_effect1_origin_y', sanitize_text_field($_POST['caa_effect1_origin_y']));
    
    // Effect 2: Blur
    update_option('caa_effect2_blur_amount', sanitize_text_field($_POST['caa_effect2_blur_amount']));
    update_option('caa_effect2_blur_scale', sanitize_text_field($_POST['caa_effect2_blur_scale']));
    update_option('caa_effect2_blur_duration', sanitize_text_field($_POST['caa_effect2_blur_duration']));
    
    // Effect 4: Text Split
    update_option('caa_effect4_text_x_range', sanitize_text_field($_POST['caa_effect4_text_x_range']));
    update_option('caa_effect4_text_y_range', sanitize_text_field($_POST['caa_effect4_text_y_range']));
    update_option('caa_effect4_stagger_amount', sanitize_text_field($_POST['caa_effect4_stagger_amount']));
    
    // Effect 5: Character Shuffle
    update_option('caa_effect5_shuffle_iterations', sanitize_text_field($_POST['caa_effect5_shuffle_iterations']));
    update_option('caa_effect5_shuffle_duration', sanitize_text_field($_POST['caa_effect5_shuffle_duration']));
    update_option('caa_effect5_char_delay', sanitize_text_field($_POST['caa_effect5_char_delay']));
    
    // Effect 6: Rotation
    update_option('caa_effect6_rotation', sanitize_text_field($_POST['caa_effect6_rotation']));
    update_option('caa_effect6_x_percent', sanitize_text_field($_POST['caa_effect6_x_percent']));
    update_option('caa_effect6_origin_x', sanitize_text_field($_POST['caa_effect6_origin_x']));
    update_option('caa_effect6_origin_y', sanitize_text_field($_POST['caa_effect6_origin_y']));
    
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved.', 'context-aware-animation') . '</p></div>';
}

// Get current settings
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

// Get effect-specific settings
$effect1_scale_down = get_option('caa_effect1_scale_down', '0');
$effect1_origin_x = get_option('caa_effect1_origin_x', '0');
$effect1_origin_y = get_option('caa_effect1_origin_y', '50');

$effect2_blur_amount = get_option('caa_effect2_blur_amount', '5');
$effect2_blur_scale = get_option('caa_effect2_blur_scale', '0.9');
$effect2_blur_duration = get_option('caa_effect2_blur_duration', '0.2');

$effect4_text_x_range = get_option('caa_effect4_text_x_range', '50');
$effect4_text_y_range = get_option('caa_effect4_text_y_range', '40');
$effect4_stagger_amount = get_option('caa_effect4_stagger_amount', '0.03');

$effect5_shuffle_iterations = get_option('caa_effect5_shuffle_iterations', '2');
$effect5_shuffle_duration = get_option('caa_effect5_shuffle_duration', '0.03');
$effect5_char_delay = get_option('caa_effect5_char_delay', '0.03');

$effect6_rotation = get_option('caa_effect6_rotation', '-90');
$effect6_x_percent = get_option('caa_effect6_x_percent', '-5');
$effect6_origin_x = get_option('caa_effect6_origin_x', '0');
$effect6_origin_y = get_option('caa_effect6_origin_y', '100');

// Enqueue admin CSS
wp_enqueue_style(
    'caa-admin',
    CAA_PLUGIN_URL . 'assets/css/admin.css',
    array(),
    CAA_VERSION
);

// Enqueue admin JavaScript for accordion functionality
wp_enqueue_script(
    'caa-admin',
    CAA_PLUGIN_URL . 'assets/js/admin.js',
    array('jquery'),
    CAA_VERSION,
    true
);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('caa_settings_nonce'); ?>
        
        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="caa_logo_id"><?php esc_html_e('Header Logo ID', 'context-aware-animation'); ?></label>
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
                            <?php esc_html_e('Enter the CSS selector for your header logo (e.g., #site-logo, .logo, or #header-logo).', 'context-aware-animation'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label><?php esc_html_e('Global Animation Settings', 'context-aware-animation'); ?></label>
                    </th>
                    <td>
                        <table class="form-table" style="margin-top: 0;">
                            <tr>
                                <th scope="row" style="width: 200px;">
                                    <label for="caa_duration"><?php esc_html_e('Animation Duration', 'context-aware-animation'); ?></label>
                                </th>
                                <td>
                                    <input 
                                        type="number" 
                                        id="caa_duration" 
                                        name="caa_duration" 
                                        value="<?php echo esc_attr($duration); ?>" 
                                        class="small-text"
                                        min="0.1"
                                        max="2"
                                        step="0.1"
                                    />
                                    <span>s</span>
                                    <p class="description">
                                        <?php esc_html_e('Duration of the animation in seconds (0.1 - 2.0).', 'context-aware-animation'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_ease"><?php esc_html_e('Easing Type', 'context-aware-animation'); ?></label>
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
                                        <?php esc_html_e('Easing function for the animation.', 'context-aware-animation'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_offset_start"><?php esc_html_e('Scroll Trigger Start Offset', 'context-aware-animation'); ?></label>
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
                                        <?php esc_html_e('Offset in pixels for when the animation starts. Use negative values to trigger earlier, positive values to trigger later.', 'context-aware-animation'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="caa_offset_end"><?php esc_html_e('Scroll Trigger End Offset', 'context-aware-animation'); ?></label>
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
                                        <?php esc_html_e('Offset in pixels for when the animation ends. Use negative values to trigger earlier, positive values to trigger later.', 'context-aware-animation'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_selected_effect"><?php esc_html_e('Effect Selection', 'context-aware-animation'); ?></label>
                    </th>
                    <td>
                        <fieldset>
                            <legend class="screen-reader-text">
                                <span><?php esc_html_e('Select Animation Effect', 'context-aware-animation'); ?></span>
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
                                    <strong><?php esc_html_e('Effect 1: Scale', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Scales down and hides the logo, then scales up and shows it.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="1" <?php echo $selected_effect === '1' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect1_scale_down"><?php esc_html_e('Scale Down Value', 'context-aware-animation'); ?></label>
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
                                                        <?php esc_html_e('Scale value when hidden (0.0 - 1.0).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect1_origin_x"><?php esc_html_e('Transform Origin X', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect1_origin_x" 
                                                        name="caa_effect1_origin_x" 
                                                        value="<?php echo esc_attr($effect1_origin_x); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="100"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal transform origin (0% - 100%).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect1_origin_y"><?php esc_html_e('Transform Origin Y', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect1_origin_y" 
                                                        name="caa_effect1_origin_y" 
                                                        value="<?php echo esc_attr($effect1_origin_y); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="100"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Vertical transform origin (0% - 100%).', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 2: Blur', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Applies blur effect and scales the logo.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="2" <?php echo $selected_effect === '2' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect2_blur_amount"><?php esc_html_e('Blur Amount', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect2_blur_amount" 
                                                        name="caa_effect2_blur_amount" 
                                                        value="<?php echo esc_attr($effect2_blur_amount); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="20"
                                                        step="0.5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Intensity of the blur effect (0 - 20px).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect2_blur_scale"><?php esc_html_e('Scale During Blur', 'context-aware-animation'); ?></label>
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
                                                        <?php esc_html_e('Scale value applied during blur (0.5 - 1.0).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect2_blur_duration"><?php esc_html_e('Blur Duration', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect2_blur_duration" 
                                                        name="caa_effect2_blur_duration" 
                                                        value="<?php echo esc_attr($effect2_blur_duration); ?>" 
                                                        class="small-text"
                                                        min="0.1"
                                                        max="1"
                                                        step="0.1"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Duration of the blur animation (0.1 - 1.0s).', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 3: Slide Text', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Slides text up and down.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="3" <?php echo $selected_effect === '3' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <p class="description" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                                            <?php esc_html_e('This effect uses global animation settings only. No additional configuration is required.', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 4: Text Split', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Splits text into characters and scatters them.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="4" <?php echo $selected_effect === '4' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect4_text_x_range"><?php esc_html_e('Random X Range', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_text_x_range" 
                                                        name="caa_effect4_text_x_range" 
                                                        value="<?php echo esc_attr($effect4_text_x_range); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="200"
                                                        step="5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Maximum horizontal displacement for characters (0 - 200px).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect4_text_y_range"><?php esc_html_e('Random Y Range', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_text_y_range" 
                                                        name="caa_effect4_text_y_range" 
                                                        value="<?php echo esc_attr($effect4_text_y_range); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="200"
                                                        step="5"
                                                    />
                                                    <span>px</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Maximum vertical displacement for characters (0 - 200px).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect4_stagger_amount"><?php esc_html_e('Stagger Amount', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect4_stagger_amount" 
                                                        name="caa_effect4_stagger_amount" 
                                                        value="<?php echo esc_attr($effect4_stagger_amount); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="0.5"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Delay between each character\'s animation (0 - 0.5s).', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 5: Character Shuffle', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Shuffles characters before revealing the original text.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="5" <?php echo $selected_effect === '5' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect5_shuffle_iterations"><?php esc_html_e('Shuffle Iterations', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_shuffle_iterations" 
                                                        name="caa_effect5_shuffle_iterations" 
                                                        value="<?php echo esc_attr($effect5_shuffle_iterations); ?>" 
                                                        class="small-text"
                                                        min="1"
                                                        max="10"
                                                        step="1"
                                                    />
                                                    <p class="description">
                                                        <?php esc_html_e('Number of times characters shuffle before revealing (1 - 10).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect5_shuffle_duration"><?php esc_html_e('Shuffle Duration', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_shuffle_duration" 
                                                        name="caa_effect5_shuffle_duration" 
                                                        value="<?php echo esc_attr($effect5_shuffle_duration); ?>" 
                                                        class="small-text"
                                                        min="0.01"
                                                        max="0.1"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Duration of each shuffle iteration (0.01 - 0.1s).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect5_char_delay"><?php esc_html_e('Character Delay', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect5_char_delay" 
                                                        name="caa_effect5_char_delay" 
                                                        value="<?php echo esc_attr($effect5_char_delay); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="0.2"
                                                        step="0.01"
                                                    />
                                                    <span>s</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Delay between each character\'s shuffle sequence (0 - 0.2s).', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 6: Rotation', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Rotates and moves the logo simultaneously.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="6" <?php echo $selected_effect === '6' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <table class="form-table" style="margin-top: 10px;">
                                            <tr>
                                                <th scope="row" style="width: 200px;">
                                                    <label for="caa_effect6_rotation"><?php esc_html_e('Rotation Angle', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_rotation" 
                                                        name="caa_effect6_rotation" 
                                                        value="<?php echo esc_attr($effect6_rotation); ?>" 
                                                        class="small-text"
                                                        min="-180"
                                                        max="180"
                                                        step="5"
                                                    />
                                                    <span>°</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Degrees of rotation (-180° - 180°).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_x_percent"><?php esc_html_e('X Percent Offset', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_x_percent" 
                                                        name="caa_effect6_x_percent" 
                                                        value="<?php echo esc_attr($effect6_x_percent); ?>" 
                                                        class="small-text"
                                                        min="-50"
                                                        max="50"
                                                        step="1"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal offset during rotation (-50% - 50%).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_origin_x"><?php esc_html_e('Transform Origin X', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_origin_x" 
                                                        name="caa_effect6_origin_x" 
                                                        value="<?php echo esc_attr($effect6_origin_x); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="100"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Horizontal pivot point (0% - 100%).', 'context-aware-animation'); ?>
                                                    </p>
                                                </td>
                                            </tr>
                                            <tr>
                                                <th scope="row">
                                                    <label for="caa_effect6_origin_y"><?php esc_html_e('Transform Origin Y', 'context-aware-animation'); ?></label>
                                                </th>
                                                <td>
                                                    <input 
                                                        type="number" 
                                                        id="caa_effect6_origin_y" 
                                                        name="caa_effect6_origin_y" 
                                                        value="<?php echo esc_attr($effect6_origin_y); ?>" 
                                                        class="small-text"
                                                        min="0"
                                                        max="100"
                                                        step="5"
                                                    />
                                                    <span>%</span>
                                                    <p class="description">
                                                        <?php esc_html_e('Vertical pivot point (0% - 100%).', 'context-aware-animation'); ?>
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
                                    <strong><?php esc_html_e('Effect 7: Move Away', 'context-aware-animation'); ?></strong>
                                    <span class="description"><?php esc_html_e(' - Moves the logo horizontally off-screen.', 'context-aware-animation'); ?></span>
                                </label>
                                <div class="caa-effect-accordion" data-effect="7" <?php echo $selected_effect === '7' ? 'style="display: block;"' : ''; ?>>
                                    <div class="caa-accordion-content">
                                        <p class="description" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-left: 4px solid #2271b1;">
                                            <?php esc_html_e('This effect uses global animation settings only. No additional configuration is required.', 'context-aware-animation'); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </fieldset>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_included_elements"><?php esc_html_e('Include Elements', 'context-aware-animation'); ?></label>
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
                            <?php esc_html_e('Enter CSS selectors (comma-separated) for elements that should trigger the animation. If left empty, the plugin will auto-detect common content areas.', 'context-aware-animation'); ?>
                            <br>
                            <?php esc_html_e('Example: #main-content, .entry-content, article, .post-content', 'context-aware-animation'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_excluded_elements"><?php esc_html_e('Excluded Elements', 'context-aware-animation'); ?></label>
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
                            <?php esc_html_e('Enter CSS selectors (comma-separated) for elements that should be excluded from collision detection. These elements will not trigger the animation.', 'context-aware-animation'); ?>
                            <br>
                            <?php esc_html_e('Example: #sidebar, .widget, .navigation, footer', 'context-aware-animation'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_global_offset"><?php esc_html_e('Global Offset', 'context-aware-animation'); ?></label>
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
                            <?php esc_html_e('Global offset in pixels to adjust when the effect is triggered. Use positive values to trigger earlier, negative values to trigger later. This offset is applied to both the start and end positions of the ScrollTrigger.', 'context-aware-animation'); ?>
                            <br>
                            <?php esc_html_e('Example: -50 (triggers 50px later), +30 (triggers 30px earlier)', 'context-aware-animation'); ?>
                        </p>
                    </td>
                </tr>
                
                <tr>
                    <th scope="row">
                        <label for="caa_debug_mode"><?php esc_html_e('Debug Mode', 'context-aware-animation'); ?></label>
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
                            <?php esc_html_e('Enable debug console output', 'context-aware-animation'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('When enabled, detailed debugging information will be logged to the browser console. Useful for troubleshooting and understanding how the plugin detects and processes elements.', 'context-aware-animation'); ?>
                        </p>
                    </td>
                </tr>
            </tbody>
        </table>
        
        <?php submit_button(); ?>
    </form>
    
    <div class="caa-info-box">
        <h2><?php esc_html_e('How It Works', 'context-aware-animation'); ?></h2>
        <p>
            <?php esc_html_e('This plugin detects when your header logo would collide with scrolling content and applies the selected animation effect to move it out of the way.', 'context-aware-animation'); ?>
        </p>
        <p>
            <?php esc_html_e('The plugin automatically detects common WordPress content areas such as:', 'context-aware-animation'); ?>
        </p>
        <ul>
            <li><code>.entry-content</code></li>
            <li><code>main</code></li>
            <li><code>.content</code></li>
            <li><code>.post-content</code></li>
            <li><code>article</code></li>
        </ul>
        <p>
            <?php esc_html_e('Use the "Include Elements" field to specify which elements should trigger the animation. If left empty, the plugin will auto-detect common content areas.', 'context-aware-animation'); ?>
        </p>
        <p>
            <?php esc_html_e('Use the "Excluded Elements" field to prevent specific elements from triggering the animation.', 'context-aware-animation'); ?>
        </p>
    </div>
</div>


