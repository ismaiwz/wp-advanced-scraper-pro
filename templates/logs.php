<div class="wrap">
    <h1><?php _e('System Logs', 'wp-advanced-scraper-pro'); ?></h1>
    
    <div class="wasp-logs-container">
        <!-- Log Filters -->
        <div class="wasp-log-filters">
            <select id="wasp-log-level-filter">
                <option value=""><?php _e('All Levels', 'wp-advanced-scraper-pro'); ?></option>
                <option value="info"><?php _e('Info', 'wp-advanced-scraper-pro'); ?></option>
                <option value="success"><?php _e('Success', 'wp-advanced-scraper-pro'); ?></option>
                <option value="warning"><?php _e('Warning', 'wp-advanced-scraper-pro'); ?></option>
                <option value="error"><?php _e('Error', 'wp-advanced-scraper-pro'); ?></option>
            </select>
            
            <button type="button" id="wasp-apply-log-filter" class="button"><?php _e('Filter', 'wp-advanced-scraper-pro'); ?></button>
            <button type="button" id="wasp-clear-logs" class="button"><?php _e('Clear Logs', 'wp-advanced-scraper-pro'); ?></button>
        </div>
        
        <!-- Logs Table -->
        <?php if (!empty($logs)): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Level', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Task', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Message', 'wp-advanced-scraper-pro'); ?></th>
                        <th><?php _e('Date', 'wp-advanced-scraper-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr class="wasp-log-row wasp-log-<?php echo esc_attr($log->level); ?>">
                            <td>
                                <span class="wasp-log-level wasp-log-level-<?php echo esc_attr($log->level); ?>">
                                    <?php echo esc_html(strtoupper($log->level)); ?>
                                </span>
                            </td>
                            <td><?php echo $log->task_name ? esc_html($log->task_name) : __('System', 'wp-advanced-scraper-pro'); ?></td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td><?php echo esc_html($log->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p><?php _e('No logs found.', 'wp-advanced-scraper-pro'); ?></p>
        <?php endif; ?>
    </div>
</div>
