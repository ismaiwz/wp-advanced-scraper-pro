<div class="wrap">
    <h1><?php _e('Advanced Scraper Pro - Dashboard', 'wp-advanced-scraper-pro'); ?></h1>
    
    <div class="wasp-dashboard">
        <div class="wasp-stats-grid">
            <div class="wasp-stat-card">
                <div class="wasp-stat-number"><?php echo esc_html($stats['total_tasks']); ?></div>
                <div class="wasp-stat-label"><?php _e('Total Tasks', 'wp-advanced-scraper-pro'); ?></div>
            </div>
            
            <div class="wasp-stat-card">
                <div class="wasp-stat-number"><?php echo esc_html($stats['active_tasks']); ?></div>
                <div class="wasp-stat-label"><?php _e('Active Tasks', 'wp-advanced-scraper-pro'); ?></div>
            </div>
            
            <div class="wasp-stat-card">
                <div class="wasp-stat-number"><?php echo esc_html($stats['total_results']); ?></div>
                <div class="wasp-stat-label"><?php _e('Total Results', 'wp-advanced-scraper-pro'); ?></div>
            </div>
            
            <div class="wasp-stat-card">
                <div class="wasp-stat-number"><?php echo esc_html($stats['published_posts']); ?></div>
                <div class="wasp-stat-label"><?php _e('Published Posts', 'wp-advanced-scraper-pro'); ?></div>
            </div>
        </div>
        
        <div class="wasp-recent-activity">
            <h2><?php _e('Recent Activity', 'wp-advanced-scraper-pro'); ?></h2>
            
            <?php if (!empty($stats['recent_logs'])): ?>
                <div class="wasp-activity-list">
                    <?php foreach ($stats['recent_logs'] as $log): ?>
                        <div class="wasp-activity-item wasp-log-<?php echo esc_attr($log->level); ?>">
                            <div class="wasp-activity-time"><?php echo esc_html($log->created_at); ?></div>
                            <div class="wasp-activity-task"><?php echo esc_html($log->task_name ?: 'System'); ?></div>
                            <div class="wasp-activity-message"><?php echo esc_html($log->message); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p><?php _e('No recent activity.', 'wp-advanced-scraper-pro'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="wasp-quick-actions">
            <h2><?php _e('Quick Actions', 'wp-advanced-scraper-pro'); ?></h2>
            <div class="wasp-action-buttons">
                <a href="<?php echo admin_url('admin.php?page=wasp-tasks'); ?>" class="button button-primary">
                    <?php _e('Create New Task', 'wp-advanced-scraper-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wasp-results'); ?>" class="button">
                    <?php _e('View Results', 'wp-advanced-scraper-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wasp-settings'); ?>" class="button">
                    <?php _e('Settings', 'wp-advanced-scraper-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>
