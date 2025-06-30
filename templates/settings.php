<div class="wrap">
    <h1><?php _e('Scraper Settings', 'wp-advanced-scraper-pro'); ?></h1>
    
    <form method="post" id="wasp-settings-form">
        <?php wp_nonce_field('wasp_settings_form', 'wasp_nonce'); ?>
        
        <h2><?php _e('General Settings', 'wp-advanced-scraper-pro'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wasp_user_agent"><?php _e('User Agent', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="text" id="wasp_user_agent" name="wasp_user_agent" value="<?php echo esc_attr(get_option('wasp_user_agent')); ?>" class="regular-text">
                    <p class="description"><?php _e('User agent string sent with HTTP requests', 'wp-advanced-scraper-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_timeout"><?php _e('Request Timeout (seconds)', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="number" id="wasp_timeout" name="wasp_timeout" value="<?php echo esc_attr(get_option('wasp_timeout')); ?>" min="5" max="300">
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_delay"><?php _e('Delay Between Requests (seconds)', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="number" id="wasp_delay" name="wasp_delay" value="<?php echo esc_attr(get_option('wasp_delay')); ?>" min="0" max="60">
                    <p class="description"><?php _e('Delay to avoid overwhelming target servers', 'wp-advanced-scraper-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_max_concurrent"><?php _e('Max Concurrent Requests', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="number" id="wasp_max_concurrent" name="wasp_max_concurrent" value="<?php echo esc_attr(get_option('wasp_max_concurrent')); ?>" min="1" max="20">
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Post Settings', 'wp-advanced-scraper-pro'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wasp_auto_publish"><?php _e('Auto Publish Posts', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wasp_auto_publish" name="wasp_auto_publish" value="1" <?php checked(get_option('wasp_auto_publish'), 1); ?>>
                    <p class="description"><?php _e('Automatically publish scraped content as posts', 'wp-advanced-scraper-pro'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_default_category"><?php _e('Default Category', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <?php wp_dropdown_categories(array(
                        'name' => 'wasp_default_category',
                        'id' => 'wasp_default_category',
                        'selected' => get_option('wasp_default_category', 1),
                        'show_option_none' => __('Select Category', 'wp-advanced-scraper-pro')
                    )); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_default_author"><?php _e('Default Author', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <?php wp_dropdown_users(array(
                        'name' => 'wasp_default_author',
                        'id' => 'wasp_default_author',
                        'selected' => get_option('wasp_default_author', 1),
                        'show_option_none' => __('Select Author', 'wp-advanced-scraper-pro')
                    )); ?>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="wasp_enable_images"><?php _e('Enable Featured Images', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wasp_enable_images" name="wasp_enable_images" value="1" <?php checked(get_option('wasp_enable_images'), 1); ?>>
                    <p class="description"><?php _e('Automatically set featured images from scraped content', 'wp-advanced-scraper-pro'); ?></p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('System Settings', 'wp-advanced-scraper-pro'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="wasp_enable_logging"><?php _e('Enable Logging', 'wp-advanced-scraper-pro'); ?></label>
                </th>
                <td>
                    <input type="checkbox" id="wasp_enable_logging" name="wasp_enable_logging" value="1" <?php checked(get_option('wasp_enable_logging'), 1); ?>>
                    <p class="description"><?php _e('Log scraping activities and errors', 'wp-advanced-scraper-pro'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(__('Save Settings', 'wp-advanced-scraper-pro'), 'primary', 'wasp_settings_submit'); ?>
    </form>
</div>
