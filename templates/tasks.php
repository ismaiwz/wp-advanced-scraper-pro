<div class="wrap">
    <h1><?php _e('Tasks Management', 'wp-advanced-scraper-pro'); ?></h1>
    
    <div class="wasp-tasks-container">
        <!-- Task Form -->
        <div class="wasp-task-form">
            <h2><?php _e('Create New Task', 'wp-advanced-scraper-pro'); ?></h2>
            
            <form method="post" id="wasp-task-form">
                <?php wp_nonce_field('wasp_task_form', 'wasp_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="task_name"><?php _e('Task Name', 'wp-advanced-scraper-pro'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="task_name" name="task_name" class="regular-text" required>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="task_url"><?php _e('URL', 'wp-advanced-scraper-pro'); ?> *</label>
                        </th>
                        <td>
                            <input type="url" id="task_url" name="task_url" class="regular-text" required>
                            <button type="button" id="test-url" class="button"><?php _e('Test URL', 'wp-advanced-scraper-pro'); ?></button>
                            <p class="description"><?php _e('Enter the URL to scrape (sitemap, RSS feed, or HTML page)', 'wp-advanced-scraper-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="scrape_type"><?php _e('Scrape Type', 'wp-advanced-scraper-pro'); ?></label>
                        </th>
                        <td>
                            <select id="scrape_type" name="scrape_type">
                                <option value="sitemap"><?php _e('XML Sitemap', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="rss"><?php _e('RSS/XML Feed', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="html"><?php _e('HTML Page', 'wp-advanced-scraper-pro'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="selectors"><?php _e('Content Selectors', 'wp-advanced-scraper-pro'); ?></label>
                        </th>
                        <td>
                            <textarea id="selectors" name="selectors" rows="8" class="large-text" placeholder="title: h1, .entry-title, .post-title
content: .entry-content, .post-content, article
excerpt: .excerpt, .summary
image: .featured-image img, .post-thumbnail img
author: .author-name, .entry-author
date: .entry-date, .post-date, time"></textarea>
                            <p class="description"><?php _e('Define CSS selectors for extracting content. Format: field: selector1, selector2', 'wp-advanced-scraper-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="filters"><?php _e('Content Filters', 'wp-advanced-scraper-pro'); ?></label>
                        </th>
                        <td>
                            <textarea id="filters" name="filters" rows="6" class="large-text" placeholder="min_words: 100
max_words: 5000
min_title_length: 10
exclude_words: spam,advertisement
required_words: news,article"></textarea>
                            <p class="description"><?php _e('Define content filtering rules. Format: rule: value', 'wp-advanced-scraper-pro'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="schedule_type"><?php _e('Schedule Type', 'wp-advanced-scraper-pro'); ?></label>
                        </th>
                        <td>
                            <select id="schedule_type" name="schedule_type">
                                <option value="manual"><?php _e('Manual', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="scheduled"><?php _e('Scheduled', 'wp-advanced-scraper-pro'); ?></option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr id="schedule_interval_row" style="display: none;">
                        <th scope="row">
                            <label for="schedule_interval"><?php _e('Schedule Interval', 'wp-advanced-scraper-pro'); ?></label>
                        </th>
                        <td>
                            <select id="schedule_interval" name="schedule_interval">
                                <option value="hourly"><?php _e('Hourly', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="twicedaily"><?php _e('Twice Daily', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="daily"><?php _e('Daily', 'wp-advanced-scraper-pro'); ?></option>
                                <option value="weekly"><?php _e('Weekly', 'wp-advanced-scraper-pro'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="wasp_task_submit" class="button-primary" value="<?php _e('Create Task', 'wp-advanced-scraper-pro'); ?>">
                </p>
            </form>
        </div>
        
        <!-- Tasks List -->
        <div class="wasp-tasks-list">
            <h2><?php _e('Existing Tasks', 'wp-advanced-scraper-pro'); ?></h2>
            
            <?php if (!empty($tasks)): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('URL', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('Type', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('Status', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('Stats', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('Last Run', 'wp-advanced-scraper-pro'); ?></th>
                            <th><?php _e('Actions', 'wp-advanced-scraper-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $task): ?>
                            <tr>
                                <td><strong><?php echo esc_html($task->name); ?></strong></td>
                                <td>
                                    <a href="<?php echo esc_url($task->url); ?>" target="_blank" title="<?php echo esc_attr($task->url); ?>">
                                        <?php echo esc_html(wp_trim_words($task->url, 6)); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="wasp-type-badge wasp-type-<?php echo esc_attr($task->scrape_type); ?>">
                                        <?php echo esc_html(strtoupper($task->scrape_type)); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="wasp-status-badge wasp-status-<?php echo esc_attr($task->status); ?>">
                                        <?php echo esc_html(ucfirst($task->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <small>
                                        <?php printf(__('Scraped: %d | Published: %d', 'wp-advanced-scraper-pro'), $task->total_scraped, $task->total_published); ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo $task->last_run ? esc_html($task->last_run) : __('Never', 'wp-advanced-scraper-pro'); ?>
                                </td>
                                <td class="wasp-actions">
                                    <button class="button button-small wasp-start-task" data-task-id="<?php echo esc_attr($task->id); ?>">
                                        <?php _e('Start', 'wp-advanced-scraper-pro'); ?>
                                    </button>
                                    <button class="button button-small wasp-delete-task" data-task-id="<?php echo esc_attr($task->id); ?>">
                                        <?php _e('Delete', 'wp-advanced-scraper-pro'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No tasks created yet.', 'wp-advanced-scraper-pro'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Progress Modal -->
    <div id="wasp-progress-modal" class="wasp-modal" style="display: none;">
        <div class="wasp-modal-content">
            <div class="wasp-modal-header">
                <h3><?php _e('Scraping Progress', 'wp-advanced-scraper-pro'); ?></h3>
            </div>
            <div class="wasp-modal-body">
                <div class="wasp-progress-bar">
                    <div class="wasp-progress-fill" style="width: 0%"></div>
                </div>
                <div class="wasp-progress-text"><?php _e('Initializing...', 'wp-advanced-scraper-pro'); ?></div>
                <div class="wasp-progress-details"></div>
            </div>
        </div>
    </div>
</div>
